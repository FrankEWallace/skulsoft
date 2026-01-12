<?php

namespace App\Services\Student;

use App\Actions\Academic\UpdateEnrollmentSeat;
use App\Actions\SendMailTemplate;
use App\Actions\Student\AssignFee;
use App\Concerns\HasCodeNumber;
use App\Enums\CustomFieldForm;
use App\Enums\OptionType;
use App\Enums\Student\RegistrationStatus;
use App\Enums\Transport\Direction;
use App\Enums\UserStatus;
use App\Http\Resources\Academic\BatchResource;
use App\Http\Resources\Academic\SubjectResource;
use App\Http\Resources\Finance\FeeConcessionResource;
use App\Http\Resources\Finance\FeeHeadResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\EnrollmentTypeResource;
use App\Http\Resources\Transport\CircleResource;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Models\CustomField;
use App\Models\Employee\Employee;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeHead;
use App\Models\GroupMember;
use App\Models\Option;
use App\Models\Student\Admission;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use App\Models\Transport\Circle;
use App\Models\User;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationActionService
{
    use FormatCodeNumber, HasCodeNumber;

    public function preRequisite(Request $request, Registration $registration): array
    {
        $statuses = RegistrationStatus::getOptions();

        $codeNumber = Arr::get($this->codeNumber($registration), 'code_number');

        $provisionalCodeNumber = Arr::get($this->codeNumber($registration, true), 'code_number');

        $batches = BatchResource::collection(Batch::query()
            ->withCount('students as max_strength')
            ->whereCourseId($registration->course_id)
            ->get());

        $feeHeads = FeeHeadResource::collection(FeeHead::query()
            ->wherePeriodId($registration->period_id)
            ->whereHas('group', function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('meta->is_custom')->orWhere('meta->is_custom', false);
                });
            })
            ->get());

        $enrollmentTypes = EnrollmentTypeResource::collection(Option::query()
            ->select('options.*', 'enrollment_seats.booked_seat', 'enrollment_seats.max_seat')
            ->byTeam()
            ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE->value)
            ->leftJoin('enrollment_seats', function ($join) use ($registration) {
                $join->on('enrollment_seats.enrollment_type_id', '=', 'options.id')
                    ->where('enrollment_seats.course_id', $registration->course_id);
            })
            ->get());

        $directions = Direction::getOptions();

        $transportCircles = CircleResource::collection(Circle::query()
            ->byPeriod($registration->period_id)
            ->get());

        $feeConcessions = FeeConcessionResource::collection(FeeConcession::query()
            ->byPeriod($registration->period_id)
            ->get());

        $electiveSubjects = SubjectResource::collection(Subject::query()
            ->withSubjectRecordByCourse($registration->course_id)
            ->where('subject_records.is_elective', true)
            ->get());

        $groups = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_GROUP->value)
            ->get());

        return compact('statuses', 'codeNumber', 'provisionalCodeNumber', 'batches', 'feeHeads', 'enrollmentTypes', 'directions', 'transportCircles', 'feeConcessions', 'electiveSubjects', 'groups');
    }

    private function codeNumber(Registration $registration, $isProvisional = false)
    {
        if ($isProvisional) {
            $numberPrefix = config('config.student.provisional_admission_number_prefix');
            $numberSuffix = config('config.student.provisional_admission_number_suffix');
            $digit = config('config.student.provisional_admission_number_digit', 0);
        } else {
            $numberPrefix = config('config.student.admission_number_prefix');
            $numberSuffix = config('config.student.admission_number_suffix');
            $digit = config('config.student.admission_number_digit', 0);
        }

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $numberFormat = $this->preFormatForAcademicCourse($registration->course_id, $numberFormat);

        if (Str::of($numberFormat)->contains('%GENDER%')) {
            $gender = $registration->contact->gender->value ?? '';
            $numberFormat = str_replace('%GENDER%', strtoupper(substr($gender, 0, 1)), $numberFormat);
        }

        if (! $isProvisional) {
            $codeNumber = (int) Admission::query()
                ->byTeam()
                ->whereNumberFormat($numberFormat)
                ->max('number') + 1;
        } else {
            $codeNumber = (int) Admission::query()
                ->byTeam()
                ->whereProvisionalNumberFormat($numberFormat)
                ->max('provisional_number') + 1;
        }

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    private function validateCodeNumber(Request $request, Registration $registration, $uuid = null): array
    {
        $existingCodeNumber = Admission::query()
            ->byTeam()
            ->whereCodeNumber($request->code_number)
            ->when($uuid, function ($q, $uuid) {
                $q->where('uuid', '!=', $uuid);
            })->count();

        if ($existingCodeNumber) {
            throw ValidationException::withMessages(['code_number' => trans('global.duplicate', ['attribute' => trans('student.admission.props.code_number')])]);
        }

        $codeNumberDetail = $this->codeNumber($registration);
        $codeNumberFormat = Arr::get($codeNumberDetail, 'number_format');

        $number = null;
        if ($request->code_number != Arr::get($codeNumberDetail, 'code_number')) {
            $number = $this->getNumberFromFormat($request->code_number, $codeNumberFormat);

            if (is_null($number)) {
                throw ValidationException::withMessages(['message' => trans('student.record.code_number_format_mismatch')]);
            }
        }

        return $request->code_number == Arr::get($codeNumberDetail, 'code_number') ? $codeNumberDetail : [
            'number_format' => $codeNumberFormat,
            'number' => $number,
            'code_number' => $request->code_number,
        ];
    }

    public function action(Request $request, Registration $registration): void
    {
        if ($request->status == 'initiated') {
            if (! $registration->is_online) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
            }

            $registration->status = RegistrationStatus::INITIATED;
            $registration->save();

            return;
        }

        if ($request->status == 'rejected') {
            $this->reject($request, $registration);
            $this->sendRejectionNotification($registration);

            return;
        }

        $this->approve($request, $registration);
    }

    private function sendRejectionNotification(Registration $registration)
    {
        if (! $registration->is_online) {
            return;
        }

        (new SendMailTemplate)->execute(
            email: $registration->contact->email,
            code: 'online-registration-rejected',
            variables: [
                'name' => $registration->contact->name,
                'reason' => $registration->rejection_remarks,
                'application_number' => $registration->getMeta('application_number'),
                'program' => $registration->course?->division?->program?->name,
                'period' => $registration->period->name,
                'course' => $registration->course->name,
            ]
        );
    }

    private function reject(Request $request, Registration $registration): void
    {
        $registration->status = RegistrationStatus::REJECTED;
        $registration->rejection_remarks = $request->rejection_remarks;
        $registration->rejected_at = now()->toDateTimeString();
        $registration->save();
    }

    private function sendApprovalNotification(Registration $registration, array $params = [])
    {
        if (empty($registration->contact->email)) {
            return;
        }

        if ($registration->is_online) {
            $template = 'online-registration-approved';
        } else {
            $template = 'registration-approved';
        }

        if (Arr::get($params, 'with_account')) {
            $template .= '-with-account';
        }

        (new SendMailTemplate)->execute(
            email: $registration->contact->email,
            code: $template,
            variables: [
                'name' => $registration->contact->name,
                'application_number' => $registration->getMeta('application_number'),
                'registration_number' => $registration->code_number,
                'program' => $registration->course?->division?->program?->name,
                'period' => $registration->period->name,
                'course' => $registration->course->name,
                'username' => Arr::get($params, 'username'),
                'password' => Arr::get($params, 'password'),
                'url' => url('/'),
            ]
        );
    }

    private function approve(Request $request, Registration $registration): void
    {
        if ($request->boolean('create_user_account')) {
            if (User::whereEmail($request->email)->exists()) {
                throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('user.user')])]);
            }
        }

        $groups = Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_GROUP->value)
            ->whereIn('uuid', $request->groups)
            ->get();

        $subjects = collect([]);
        if ($request->elective_subjects) {
            $subjects = Subject::query()
                ->whereIn('subjects.uuid', $request->elective_subjects)
                ->get();

            $electiveSubjects = Subject::query()
                ->withSubjectRecord($request->batch_id, $registration->course_id)
                ->where('subject_records.is_elective', true)
                ->get();

            $missingSubjects = $subjects->pluck('name')->diff($electiveSubjects->pluck('name'))->all();

            if ($missingSubjects) {
                throw ValidationException::withMessages(['message' => trans('student.registration.could_not_find_elective_subjects', ['attribute' => implode(', ', $missingSubjects)])]);
            }
        }

        if ($request->is_provisional) {
            $codeNumberDetail = $this->codeNumber($registration, true);
        } else {
            $codeNumberDetail = $this->validateCodeNumber($request, $registration);
        }

        \DB::beginTransaction();

        $registration->status = RegistrationStatus::APPROVED;
        $registration->save();

        $admission = Admission::forceCreate([
            'is_provisional' => $request->boolean('is_provisional'),
            'registration_id' => $registration->id,
            'batch_id' => $request->batch_id,
            'joining_date' => $request->date,
            'remarks' => $request->remarks,
        ]);

        if ($admission->is_provisional) {
            $admission->provisional_number_format = Arr::get($codeNumberDetail, 'number_format');
            $admission->provisional_number = Arr::get($codeNumberDetail, 'number');
            $admission->provisional_code_number = Arr::get($codeNumberDetail, 'code_number');
            $admission->code_number = Arr::get($codeNumberDetail, 'code_number');
        } else {
            $admission->number_format = Arr::get($codeNumberDetail, 'number_format');
            $admission->number = Arr::get($codeNumberDetail, 'number');
            $admission->code_number = Arr::get($codeNumberDetail, 'code_number');
        }

        $admission->save();

        $student = Student::forceCreate([
            'admission_id' => $admission->id,
            'period_id' => $registration->period_id,
            'batch_id' => $request->batch_id,
            'contact_id' => $registration->contact_id,
            'start_date' => $request->date,
            'enrollment_type_id' => $request->enrollment_type_id,
        ]);

        $isNewStudent = false;
        if ($student->start_date->value == $admission->joining_date->value) {
            $isNewStudent = true;
        }

        if ($request->boolean('assign_fee')) {
            (new AssignFee)->execute(
                student: $student,
                feeConcession: $request->fee_concession,
                transportCircle: $request->transport_circle,
                params: [
                    'direction' => $request->direction,
                    'opted_fee_heads' => $request->opted_fee_heads,
                    'is_new_student' => $isNewStudent,
                ]
            );
        }

        if ($request->elective_subjects) {
            foreach ($request->elective_subjects as $subject) {
                $subject = $subjects->where('uuid', $subject)->first();

                SubjectWiseStudent::firstOrCreate([
                    'batch_id' => $request->batch_id,
                    'subject_id' => $subject->id,
                    'student_id' => $student->id,
                ]);
            }
        }

        foreach ($groups as $group) {
            GroupMember::forceCreate([
                'model_type' => 'Student',
                'model_id' => $student->id,
                'model_group_id' => $group->id,
            ]);
        }

        if ($request->boolean('create_user_account')) {

            $contact = $registration->contact;

            if (! $contact->email) {
                $contact->email = $request->email;
                $contact->save();
            }

            $user = User::forceCreate([
                'name' => $student->contact->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'status' => UserStatus::ACTIVATED,
            ]);

            $user->assignRole('student');

            $contact = $registration->contact;
            $contact->user_id = $user->id;
            $contact->save();
        }

        (new UpdateEnrollmentSeat)->execute($registration->course);

        $this->updateRegistrationCustomFields($registration, $student);

        \DB::commit();

        $registration->refresh();

        $this->sendApprovalNotification($registration, [
            'with_account' => $request->boolean('create_user_account') ? true : false,
            'is_provisional' => $request->boolean('is_provisional'),
            'code_number' => $admission->code_number,
            'username' => $request->username,
            'password' => $request->password,
        ]);
    }

    private function updateRegistrationCustomFields(Registration $registration, Student $student)
    {
        $customFields = CustomField::query()
            ->byTeam()
            ->whereIn('form', [CustomFieldForm::STUDENT, CustomFieldForm::REGISTRATION])
            ->orderBy('position')
            ->get();

        $registrationCustomFields = collect($registration->getMeta('custom_fields', []));

        $customFieldValues = [];
        foreach ($customFields->where('form.value', CustomFieldForm::REGISTRATION) as $customField) {
            $registrationCustomFieldValue = $registrationCustomFields->firstWhere('uuid', $customField->uuid);

            $studentCustomField = $customFields->where('form.value', CustomFieldForm::STUDENT)->where('label', $customField->label)->first();

            if ($studentCustomField) {
                $customFieldValues[] = [
                    'uuid' => $studentCustomField->uuid,
                    'value' => Arr::get($registrationCustomFieldValue, 'value'),
                ];
            }
        }

        $contact = $student->contact;
        $contact->setMeta([
            'custom_fields' => $customFieldValues,
        ]);
        $contact->save();
    }

    public function updateBulkAssignTo(Request $request)
    {
        $request->validate([
            'registrations' => 'array',
            'employee' => 'required|uuid',
        ]);

        $employee = Employee::query()
            ->byTeam()
            ->whereUuid($request->input('employee'))
            ->getOrFail(__('employee.employee'), 'employee');

        $registrations = Registration::query()
            ->whereIn('uuid', $request->input('registrations', []))
            ->get();

        foreach ($registrations as $registration) {
            $registration->employee_id = $employee->id;
            $registration->save();
        }
    }

    public function updateBulkStage(Request $request)
    {
        $request->validate([
            'registrations' => 'array',
            'stage' => 'required|uuid',
        ]);

        $stage = Option::query()
            ->byTeam()
            ->where('type', OptionType::REGISTRATION_STAGE)
            ->whereUuid($request->input('stage'))
            ->first();

        if (! $stage) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $registrations = Registration::query()
            ->whereIn('uuid', $request->input('registrations', []))
            ->get();

        foreach ($registrations as $registration) {
            $registration->stage_id = $stage->id;
            $registration->save();
        }
    }
}
