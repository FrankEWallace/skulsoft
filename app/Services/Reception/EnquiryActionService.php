<?php

namespace App\Services\Reception;

use App\Enums\CustomFieldForm;
use App\Enums\Finance\PaymentStatus;
use App\Enums\OptionType;
use App\Enums\Reception\EnquiryNature;
use App\Enums\Reception\EnquiryStatus;
use App\Enums\Student\RegistrationStatus;
use App\Models\Academic\Course;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\Reception\Enquiry;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use App\Services\Student\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EnquiryActionService
{
    public function convertToRegistration(Request $request, Enquiry $enquiry, array $params = [])
    {
        if ($enquiry->nature != EnquiryNature::ADMISSION) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($enquiry->getMeta('is_converted')) {
            throw ValidationException::withMessages(['message' => trans('reception.enquiry.already_converted')]);
        }

        \DB::beginTransaction();

        $contact = $enquiry->contact;

        $this->validateData($enquiry, $contact);

        $course = Course::query()
            ->where('id', $enquiry->course_id)
            ->firstOrFail();

        $registrationFee = $course->registration_fee->value;

        $codeNumberDetail = (new RegistrationService)->codeNumber($enquiry->course_id);

        $registration = Registration::forceCreate([
            'period_id' => $enquiry->period_id,
            'course_id' => $enquiry->course_id,
            'date' => today()->toDateString(),
            'remarks' => trans('reception.enquiry.converted_to_registration'),
            'fee' => $registrationFee,
            'payment_status' => $registrationFee ? PaymentStatus::UNPAID : PaymentStatus::NA,
            'contact_id' => $contact->id,
            'code_number' => Arr::get($codeNumberDetail, 'code_number'),
            'number_format' => Arr::get($codeNumberDetail, 'number_format'),
            'number' => Arr::get($codeNumberDetail, 'number'),
            'status' => RegistrationStatus::PENDING,
            'is_online' => false,
            'employee_id' => Arr::get($params, 'employee_id'),
            'meta' => [
                'is_converted' => true,
                'enquiry_uuid' => $enquiry->uuid,
            ],
        ]);

        $enquiry->setMeta([
            'registration_uuid' => $registration->uuid,
            'is_converted' => true,
        ]);
        $enquiry->status = EnquiryStatus::CLOSE;
        $enquiry->save();

        $customFields = CustomField::query()
            ->byTeam()
            ->whereIn('form', [CustomFieldForm::ENQUIRY, CustomFieldForm::REGISTRATION])
            ->orderBy('position')
            ->get();

        $enquiryCustomFields = collect($enquiry->getMeta('custom_fields', []));

        $customFieldValues = [];
        foreach ($customFields->where('form.value', CustomFieldForm::ENQUIRY) as $customField) {
            $enquiryCustomFieldValue = $enquiryCustomFields->firstWhere('uuid', $customField->uuid);

            $registrationCustomField = $customFields->where('form.value', CustomFieldForm::REGISTRATION)->where('label', $customField->label)->first();

            if ($registrationCustomField) {
                $customFieldValues[] = [
                    'uuid' => $registrationCustomField->uuid,
                    'value' => Arr::get($enquiryCustomFieldValue, 'value'),
                ];
            }
        }

        $registration->setMeta([
            'custom_fields' => $customFieldValues,
        ]);
        $registration->save();

        \DB::commit();
    }

    public function bulkConvertToRegistration(Request $request)
    {
        $request->validate([
            'enquiries' => 'array',
            'employee' => 'nullable|uuid',
        ]);

        $employee = $request->employee ? Employee::query()
            ->byTeam()
            ->whereUuid($request->input('employee'))
            ->getOrFail(__('employee.employee'), 'employee') : null;

        $uuids = $request->input('enquiries', []);

        $enquiries = Enquiry::query()
            ->whereIn('uuid', $uuids)
            ->where('nature', EnquiryNature::ADMISSION)
            ->where(function ($q) {
                $q->whereNull('meta->is_converted')->orWhere('meta->is_converted', false);
            })
            ->get();

        $convertedCount = 0;
        foreach ($enquiries as $enquiry) {
            $params['code_number_detail'] = (new RegistrationService)->codeNumber($enquiry->course_id);
            $params['employee_id'] = $employee?->id;

            $this->convertToRegistration($request, $enquiry, $params);

            $convertedCount++;
        }

        if ($convertedCount == 0) {
            throw ValidationException::withMessages(['message' => trans('reception.enquiry.could_not_convert_to_registration')]);
        }

        return $convertedCount;
    }

    private function validateData(Enquiry $enquiry, Contact $contact)
    {
        $existingRegistration = Registration::query()
            ->whereContactId($contact->id)
            ->wherePeriodId($enquiry->period_id)
            ->whereCourseId($enquiry->course_id)
            ->where('date', '=', today()->toDateString())
            ->exists();

        if ($existingRegistration) {
            throw ValidationException::withMessages(['date' => trans('global.duplicate', ['attribute' => trans('student.registration.registration')])]);
        }

        $existingStudent = Student::query()
            ->whereContactId($contact->id)
            ->where('period_id', '=', $enquiry->period_id)
            ->whereHas('batch', function ($q) use ($enquiry) {
                $q->where('course_id', $enquiry->course_id);
            })
            ->whereHas('admission', function ($q) {
                $q->whereNull('leaving_date')->orWhere(function ($q1) {
                    $q1->whereNotNull('leaving_date')->where('leaving_date', '>', today()->toDateString());
                });
            })->exists();

        if ($existingStudent) {
            throw ValidationException::withMessages(['date' => trans('global.duplicate', ['attribute' => trans('student.student')])]);
        }
    }

    public function updateBulkAssignTo(Request $request)
    {
        $request->validate([
            'enquiries' => 'array',
            'employee' => 'required|uuid',
        ]);

        $employee = Employee::query()
            ->byTeam()
            ->whereUuid($request->input('employee'))
            ->getOrFail(__('employee.employee'), 'employee');

        $enquiries = Enquiry::query()
            ->whereIn('uuid', $request->input('enquiries', []))
            ->get();

        foreach ($enquiries as $enquiry) {
            $enquiry->employee_id = $employee->id;
            $enquiry->save();
        }
    }

    public function updateBulkStage(Request $request)
    {
        $request->validate([
            'enquiries' => 'array',
            'stage' => 'required|uuid',
        ]);

        $stage = Option::query()
            ->byTeam()
            ->where('type', OptionType::ENQUIRY_STAGE)
            ->whereUuid($request->input('stage'))
            ->first();

        if (! $stage) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $enquiries = Enquiry::query()
            ->whereIn('uuid', $request->input('enquiries', []))
            ->get();

        foreach ($enquiries as $enquiry) {
            $enquiry->stage_id = $stage->id;
            $enquiry->save();
        }
    }

    public function updateBulkType(Request $request)
    {
        $request->validate([
            'enquiries' => 'array',
            'type' => 'required|uuid',
        ]);

        $type = Option::query()
            ->byTeam()
            ->where('type', OptionType::ENQUIRY_TYPE)
            ->whereUuid($request->input('type'))
            ->first();

        if (! $type) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $enquiries = Enquiry::query()
            ->whereIn('uuid', $request->input('enquiries', []))
            ->get();

        foreach ($enquiries as $enquiry) {
            $enquiry->type_id = $type->id;
            $enquiry->save();
        }
    }

    public function updateBulkSource(Request $request)
    {
        $request->validate([
            'enquiries' => 'array',
            'source' => 'required|uuid',
        ]);

        $source = Option::query()
            ->byTeam()
            ->where('type', OptionType::ENQUIRY_SOURCE)
            ->whereUuid($request->input('source'))
            ->first();

        if (! $source) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $enquiries = Enquiry::query()
            ->whereIn('uuid', $request->input('enquiries', []))
            ->get();

        foreach ($enquiries as $enquiry) {
            $enquiry->source_id = $source->id;
            $enquiry->save();
        }
    }
}
