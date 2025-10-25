<?php

namespace App\Services\Student;

use App\Actions\CreateContact;
use App\Actions\UpdateContact;
use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\FamilyRelation;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Gender;
use App\Enums\Locality;
use App\Enums\OptionType;
use App\Enums\Reception\EnquiryStatus;
use App\Enums\Student\AdmissionType;
use App\Enums\Student\RegistrationStatus;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\CustomFieldResource;
use App\Http\Resources\OptionResource;
use App\Models\Academic\Period;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\Finance\Transaction;
use App\Models\Guardian;
use App\Models\Option;
use App\Models\Reception\Enquiry;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    use FormatCodeNumber;

    public function codeNumber(int $courseId): array
    {
        $numberPrefix = config('config.student.registration_number_prefix');
        $numberSuffix = config('config.student.registration_number_suffix');
        $digit = config('config.student.registration_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $numberFormat = $this->preFormatForAcademicCourse($courseId, $numberFormat);

        $codeNumber = (int) Registration::query()
            ->join('periods', 'periods.id', '=', 'registrations.period_id')
            ->when(auth()->check(), function ($q) {
                $q->where('periods.team_id', auth()->user()?->current_team_id);
            })
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(): array
    {
        $genders = Gender::getOptions();

        $relations = FamilyRelation::getOptions();

        $studentTypes = [
            ['label' => trans('global.new', ['attribute' => trans('student.student')]), 'value' => 'new'],
            ['label' => trans('global.existing', ['attribute' => trans('student.student')]), 'value' => 'existing'],
        ];

        $guardianTypes = [
            ['label' => trans('global.new', ['attribute' => trans('guardian.guardian')]), 'value' => 'new'],
            ['label' => trans('global.existing', ['attribute' => trans('guardian.guardian')]), 'value' => 'existing'],
        ];

        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->get());

        $enrollmentTypes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::STUDENT_ENROLLMENT_TYPE)
            ->get());

        $stages = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::REGISTRATION_STAGE)
            ->get());

        $castes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CASTE)
            ->get());

        $categories = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::MEMBER_CATEGORY)
            ->get());

        $religions = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::RELIGION)
            ->get());

        $bloodGroups = BloodGroup::getOptions();

        $localities = Locality::getOptions();

        $customFields = CustomFieldResource::collection(CustomField::query()
            ->byTeam()
            ->whereForm(CustomFieldForm::REGISTRATION)
            ->orderBy('position')
            ->get());

        $statuses = RegistrationStatus::getOptions();

        $types = [
            ['label' => trans('student.registration.online'), 'value' => 'online'],
            ['label' => trans('student.registration.offline'), 'value' => 'offline'],
        ];

        $admissionTypes = AdmissionType::getOptions();

        return compact('genders', 'relations', 'studentTypes', 'guardianTypes', 'periods', 'statuses', 'types', 'customFields', 'castes', 'categories', 'enrollmentTypes', 'stages', 'religions', 'bloodGroups', 'localities', 'admissionTypes');
    }

    public function create(Request $request): Registration
    {
        \DB::beginTransaction();

        if ($request->student_type == 'new') {
            $params = $request->all();
            $params['source'] = 'student';

            $contact = (new CreateContact)->execute($params);

            $request->merge([
                'contact_id' => $contact->id,
            ]);
        }

        $this->validateInput($request);

        throw ValidationException::withMessages(['message' => 'test']);

        $registration = Registration::forceCreate($this->formatParams($request));

        foreach ($request->guardians as $index => $guardian) {
            $guardianType = Arr::get($guardian, 'guardian_type');

            if ($guardianType == 'new') {
                $newGuardianContact = (new CreateContact)->execute($guardian);

                if (Arr::get($guardian, 'relation') == 'father') {
                    $contact->father_name = $newGuardianContact->name;
                } elseif (Arr::get($guardian, 'relation') == 'mother') {
                    $contact->mother_name = $newGuardianContact->name;
                }
            }

            $secondaryContactId = $guardianType == 'new' ? $newGuardianContact->id : Arr::get($guardian, 'guardian_id');

            $existingGuardian = Guardian::query()
                ->wherePrimaryContactId($request->contact_id)
                ->whereContactId($secondaryContactId)
                ->first();

            if ($existingGuardian) {
                if ($existingGuardian->relation == 'father') {
                    $contact->father_name = $existingGuardian->contact->name;
                } elseif ($existingGuardian->relation == 'mother') {
                    $contact->mother_name = $existingGuardian->contact->name;
                }
            } else {
                $guardianRelation = Guardian::firstOrCreate([
                    'primary_contact_id' => $request->contact_id,
                    'contact_id' => $secondaryContactId,
                    'relation' => Arr::get($guardian, 'relation'),
                ]);

                $guardianRelation->update([
                    'position' => $index + 1,
                ]);
            }
        }

        if (isset($contact)) {
            $contact->save();
        }

        \DB::commit();

        return $registration;
    }

    public function createOnline(Request $request): Registration
    {
        \DB::beginTransaction();

        $contact = (new CreateContact)->execute($request->all());

        $existingPendingRegistration = Registration::query()
            ->whereContactId($contact->id)
            ->whereCourseId($request->course_id)
            ->whereStatus(RegistrationStatus::PENDING)
            ->exists();

        if ($existingPendingRegistration) {
            throw ValidationException::withMessages(['message' => trans('global.duplicate', ['attribute' => trans('student.registration.registration')])]);
        }

        $this->updateContactAddress($request, $contact);

        $request->merge([
            'date' => today()->format('Y-m-d'),
            'is_online' => true,
            'contact_id' => $contact->id,
        ]);

        $this->validateInput($request);

        $registration = Registration::forceCreate($this->formatParams($request));

        $registration->addMedia($request);

        \DB::commit();

        return $registration;
    }

    private function updateContactAddress(Request $request, Contact $contact)
    {
        $address = $request->present_address;

        $contact->address = [
            'present' => [
                'address_line1' => Arr::get($address, 'address_line1'),
                'address_line2' => Arr::get($address, 'address_line2'),
                'city' => Arr::get($address, 'city'),
                'state' => Arr::get($address, 'state'),
                'zipcode' => Arr::get($address, 'zipcode'),
                'country' => Arr::get($address, 'country'),
            ],
        ];
        $contact->save();
    }

    private function validateInput(Request $request)
    {
        $existingRegistration = Registration::query()
            ->whereContactId($request->contact_id)
            ->wherePeriodId($request->period_id)
            ->whereCourseId($request->course_id)
            ->first();

        if ($existingRegistration && in_array($existingRegistration->status, [RegistrationStatus::PENDING, RegistrationStatus::INITIATED])) {
            throw ValidationException::withMessages(['date' => trans('global.duplicate', ['attribute' => trans('student.registration.registration')])]);
        }

        if ($existingRegistration && $existingRegistration->date->value == $request->date) {
            throw ValidationException::withMessages(['date' => trans('global.duplicate', ['attribute' => trans('student.registration.registration')])]);
        }

        // student studying in same course or same program cannot register again
        $existingStudent = Student::query()
            ->whereContactId($request->contact_id)
            ->where('period_id', '=', $request->period_id)
            ->whereHas('batch', function ($q) use ($request) {
                $q->where('course_id', $request->course_id)
                    ->orWhereHas('course', function ($q) use ($request) {
                        $q->whereHas('division', function ($q1) use ($request) {
                            $q1->where('program_id', $request->program_id);
                        });
                    });
            })
            ->whereHas('admission', function ($q) use ($request) {
                $q->whereNull('leaving_date')->orWhere(function ($q1) use ($request) {
                    $q1->whereNotNull('leaving_date')->where('leaving_date', '>', $request->date);
                });
            })->exists();

        if ($existingStudent) {
            throw ValidationException::withMessages(['date' => trans('global.duplicate', ['attribute' => trans('student.student')])]);
        }
    }

    private function formatParams(Request $request, ?Registration $registration = null): array
    {
        $formatted = [
            'period_id' => $request->period_id,
            'course_id' => $request->course_id,
            'enrollment_type_id' => $request->enrollment_type_id,
            'date' => $request->date,
            'remarks' => $request->remarks,
            'fee' => $request->registration_fee,
            'payment_status' => $request->registration_fee ? PaymentStatus::UNPAID : PaymentStatus::NA,
        ];

        if (! $registration) {
            $codeNumberDetail = $this->codeNumber($request->course_id);

            $formatted['contact_id'] = $request->contact_id;
            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
            $formatted['is_online'] = $request->boolean('is_online');
            $formatted['status'] = RegistrationStatus::PENDING;

            if ($request->boolean('is_online')) {
                $formatted['meta']['application_number'] = strtoupper(date('Ymd').Str::random(8));
            }
        } else {
            $formatted['fee'] = $request->registration_fee;

            $status = $this->getFeeStatus($request, $formatted);
            $formatted['payment_status'] = $status;
        }

        $meta = $request->meta ?? [];
        $meta['custom_fields'] = $request->custom_fields;

        if ($registration && $request->payment_due_date) {
            $meta['payment_due_date'] = $request->payment_due_date;
        }

        $formatted['meta'] = $meta;

        return $formatted;
    }

    private function getFeeStatus(Request $request)
    {
        if ($request->registration_fee == 0) {
            return PaymentStatus::NA;
        } elseif ($request->paid > 0 && $request->paid < $request->registration_fee) {
            return PaymentStatus::PARTIALLY_PAID;
        } elseif ($request->paid == $request->registration_fee) {
            return PaymentStatus::PAID;
        }

        return PaymentStatus::UNPAID;
    }

    private function validateUpdate(Request $request, Registration $registration): void
    {
        if (! $registration->isEditable()) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        if ($request->payment_due_date && $request->payment_due_date < $registration->date->value) {
            throw ValidationException::withMessages(['payment_due_date' => trans('student.registration.payment_due_date_cannot_be_less_than_registration_date')]);
        }

        $paidAmount = Transaction::query()
            ->whereTransactionableId($registration->id)
            ->whereTransactionableType('Registration')
            ->whereHead('registration_fee')
            ->whereNull('cancelled_at')
            ->whereNull('rejected_at')
            ->where(function ($q) {
                $q->where('is_online', false)
                    ->orWhere(function ($q) {
                        $q->where('is_online', true)
                            ->whereNotNull('processed_at');
                    });
            })
            ->sum('amount');

        if ($paidAmount > 0 && $request->registration_fee < $paidAmount) {
            throw ValidationException::withMessages(['registration_fee' => trans('student.registration.fee_cannot_be_less_than_paid')]);
        }

        $request->merge([
            'paid' => $paidAmount,
        ]);
    }

    public function update(Request $request, Registration $registration): void
    {
        $this->validateUpdate($request, $registration);

        \DB::beginTransaction();

        $registration->forceFill($this->formatParams($request, $registration))->save();

        \DB::commit();
    }

    public function updateDetail(Request $request, Registration $registration): void
    {
        $this->validateUpdate($request, $registration);

        \DB::beginTransaction();

        if ($request->tab == 'basic') {
            $registration->period_id = $request->period_id;
            $registration->course_id = $request->course_id;
            $registration->enrollment_type_id = $request->enrollment_type_id;
            $registration->stage_id = $request->stage_id;
            $registration->date = $request->date;
            $registration->remarks = $request->remarks;
            $registration->fee = $request->registration_fee;
            if ($request->payment_due_date) {
                $registration->setMeta([
                    'payment_due_date' => $request->payment_due_date,
                ]);
            }

            $status = $this->getFeeStatus($request);
            $registration->payment_status = $status;
            $registration->save();
        }

        $contact = $registration->contact;
        $request->merge([
            'contact_id' => $contact->id,
        ]);

        if (in_array($request->tab, ['contact', 'basic'])) {
            (new UpdateContact)->execute($contact, $request->all());
        }

        if ($request->tab == 'guardian') {
            $this->updateGuardian($request, $registration);
        }

        \DB::commit();
    }

    private function updateGuardian(Request $request, Registration $registration): void
    {
        $contact = $registration->contact;

        foreach ($request->guardians as $index => $guardian) {
            $guardianType = Arr::get($guardian, 'guardian_type', 'new');

            if ($guardianType == 'new') {
                $newGuardianContact = (new CreateContact)->execute($guardian);

                $newGuardianContact->occupation = Arr::get($guardian, 'occupation');
                $newGuardianContact->save();

                if (Arr::get($guardian, 'relation') == 'father') {
                    $contact->father_name = $newGuardianContact->name;
                } elseif (Arr::get($guardian, 'relation') == 'mother') {
                    $contact->mother_name = $newGuardianContact->name;
                }
                $contact->save();
                $secondaryContactId = $newGuardianContact->id;
            } else {
                $existingGuardian = Guardian::query()
                    ->with('contact')
                    ->whereUuid(Arr::get($guardian, 'guardian'))
                    ->wherePrimaryContactId($contact->id)
                    ->first();

                if (! $existingGuardian) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('guardian.guardian')])]);
                }

                $existingGuardian->relation = Arr::get($guardian, 'relation');
                $existingGuardian->save();

                $existingGuardianContact = $existingGuardian->contact;
                (new UpdateContact)->execute($existingGuardianContact, [
                    'name' => Arr::get($guardian, 'name'),
                    'contact_number' => Arr::get($guardian, 'contact_number'),
                    'occupation' => Arr::get($guardian, 'occupation'),
                ]);

                $secondaryContactId = $existingGuardianContact->id;
            }

            $existingGuardian = Guardian::query()
                ->wherePrimaryContactId($contact->id)
                ->whereContactId($secondaryContactId)
                ->first();

            if ($existingGuardian) {
                if ($existingGuardian->relation == 'father') {
                    $contact->father_name = $existingGuardian->contact->name;
                } elseif ($existingGuardian->relation == 'mother') {
                    $contact->mother_name = $existingGuardian->contact->name;
                }
                $contact->save();
            } else {
                $guardianRelation = Guardian::firstOrCreate([
                    'primary_contact_id' => $request->contact_id,
                    'contact_id' => $secondaryContactId,
                    'relation' => Arr::get($guardian, 'relation'),
                ]);

                $guardianRelation->update([
                    'position' => $index + 1,
                ]);
            }
        }
    }

    public function deletable(Registration $registration, $validate = false): ?bool
    {
        if (! $registration->isEditable()) {
            if ($validate) {
                return false;
            }

            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        return true;
    }

    public function delete(Registration $registration): void
    {
        \DB::beginTransaction();

        if ($registration->getMeta('is_converted')) {
            $enquiry = Enquiry::where('meta->registration_uuid', $registration->uuid)->first();

            if ($enquiry) {
                $enquiry->setMeta([
                    'is_converted' => false,
                    'registration_uuid' => null,
                ]);
                $enquiry->status = EnquiryStatus::OPEN;
                $enquiry->save();
            }
        }

        $registration->delete();

        \DB::commit();
    }

    private function findMultiple(Request $request): array
    {
        if ($request->boolean('global')) {
            $listService = new RegistrationListService;
            $uuids = $listService->getIds($request);
        } else {
            $uuids = is_array($request->uuids) ? $request->uuids : [];
        }

        if (! count($uuids)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.data')])]);
        }

        return $uuids;
    }

    public function deleteMultiple(Request $request): int
    {
        $uuids = $this->findMultiple($request);

        $registrations = Registration::whereIn('uuid', $uuids)->get();

        $deletable = [];
        foreach ($registrations as $registration) {
            if ($this->deletable($registration, true)) {
                $deletable[] = $registration->uuid;
            }
        }

        if (! count($deletable)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_delete_any', ['attribute' => trans('student.registration.registration')])]);
        }

        \DB::beginTransaction();

        $registrations = Registration::query()
            ->whereIn('uuid', $deletable)
            ->where('meta->is_converted', true)
            ->get();

        $enquiryUuids = [];
        foreach ($registrations as $registration) {
            $enquiryUuids[] = $registration->getMeta('enquiry_uuid');
        }

        $enquiries = Enquiry::whereIn('uuid', $enquiryUuids)->get();
        foreach ($enquiries as $enquiry) {
            $enquiry->setMeta([
                'is_converted' => false,
                'registration_uuid' => null,
            ]);
            $enquiry->status = EnquiryStatus::OPEN;
            $enquiry->save();
        }

        Registration::whereIn('uuid', $deletable)->delete();

        \DB::commit();

        return count($deletable);
    }
}
