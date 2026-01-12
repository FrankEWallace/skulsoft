<?php

namespace App\Services\Student;

use App\Actions\Config\SetTeamWiseModuleConfig;
use App\Actions\CreateContact;
use App\Actions\SendMailTemplate;
use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\FamilyRelation;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Enums\Student\RegistrationStatus;
use App\Http\Resources\Academic\CourseForGuestResource;
use App\Http\Resources\Academic\PeriodForGuestResource;
use App\Http\Resources\Academic\ProgramForGuestResource;
use App\Http\Resources\CustomFieldResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\TeamForGuestResource;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\Program;
use App\Models\Config\Config;
use App\Models\CustomField;
use App\Models\Option;
use App\Models\Student\Registration;
use App\Models\Team;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlineRegistrationService
{
    use FormatCodeNumber;

    public function setFinanceConfig(int $teamId, string $module = 'finance')
    {
        (new SetTeamWiseModuleConfig)->execute($teamId, $module);
    }

    private function codeNumber(int $courseId, ?int $teamId = null): array
    {
        $numberPrefix = config('config.student.registration_number_prefix');
        $numberSuffix = config('config.student.registration_number_suffix');
        $digit = config('config.student.registration_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $numberFormat = $this->preFormatForAcademicCourse($courseId, $numberFormat);

        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $codeNumber = (int) Registration::query()
            ->join('periods', 'periods.id', '=', 'registrations.period_id')
            ->when($teamId, function ($q) use ($teamId) {
                $q->where('periods.team_id', $teamId);
            })
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request)
    {
        $teamId = null;
        if ($request->query('number')) {
            $registration = Registration::query()
                ->where('meta->application_number', $request->query('number'))
                ->first();

            $teamId = $registration?->period?->team_id;
        }

        $instruction = nl2br(config('config.feature.online_registration_instruction'));

        if (! $teamId) {
            $teams = TeamForGuestResource::collection(Team::query()
                ->get());

            $genders = Gender::getOptions();

            return compact('teams', 'instruction', 'genders');
        }

        $genders = Gender::getOptions();

        $relations = FamilyRelation::getOptions();

        $bloodGroups = BloodGroup::getOptions();

        $maritalStatuses = MaritalStatus::getOptions();

        $config = Config::query()
            ->where('name', 'student')
            ->whereTeamId($teamId)
            ->first()
            ?->value;

        $fields = [
            'is_unique_id_number1_enabled' => Arr::get($config, 'is_unique_id_number1_enabled', true),
            'is_unique_id_number2_enabled' => Arr::get($config, 'is_unique_id_number2_enabled', true),
            'is_unique_id_number3_enabled' => Arr::get($config, 'is_unique_id_number3_enabled', true),
            'is_unique_id_number4_enabled' => Arr::get($config, 'is_unique_id_number4_enabled', true),
            'is_unique_id_number5_enabled' => Arr::get($config, 'is_unique_id_number5_enabled', true),
            'unique_id_number1_label' => Arr::get($config, 'unique_id_number1_label', 'Unique ID Number 1'),
            'unique_id_number2_label' => Arr::get($config, 'unique_id_number2_label', 'Unique ID Number 2'),
            'unique_id_number3_label' => Arr::get($config, 'unique_id_number3_label', 'Unique ID Number 3'),
            'unique_id_number4_label' => Arr::get($config, 'unique_id_number4_label', 'Unique ID Number 4'),
            'unique_id_number5_label' => Arr::get($config, 'unique_id_number5_label', 'Unique ID Number 5'),
            'is_unique_id_number1_required' => Arr::get($config, 'is_unique_id_number1_required', false),
            'is_unique_id_number2_required' => Arr::get($config, 'is_unique_id_number2_required', false),
            'is_unique_id_number3_required' => Arr::get($config, 'is_unique_id_number3_required', false),
            'is_unique_id_number4_required' => Arr::get($config, 'is_unique_id_number4_required', false),
            'is_unique_id_number5_required' => Arr::get($config, 'is_unique_id_number5_required', false),
        ];

        $categories = OptionResource::collection(Option::query()
            ->byTeam($teamId)
            ->where('type', OptionType::MEMBER_CATEGORY->value)
            ->get());

        $castes = OptionResource::collection(Option::query()
            ->byTeam($teamId)
            ->where('type', OptionType::MEMBER_CASTE->value)
            ->get());

        $religions = OptionResource::collection(Option::query()
            ->byTeam($teamId)
            ->where('type', OptionType::RELIGION->value)
            ->get());

        $customFields = CustomFieldResource::collection(CustomField::query()
            ->byTeam($teamId)
            ->whereForm(CustomFieldForm::REGISTRATION)
            ->orderBy('position')
            ->get());

        return compact('instruction', 'genders', 'relations', 'bloodGroups', 'maritalStatuses', 'categories', 'castes', 'religions', 'customFields', 'fields');
    }

    public function getPrograms(Team $team)
    {
        $programs = ProgramForGuestResource::collection(Program::query()
            ->where('team_id', $team->id)
            ->where('config->enable_registration', true)
            ->get());

        return compact('programs');
    }

    public function getPeriods(Team $team)
    {
        $periods = PeriodForGuestResource::collection(Period::query()
            ->where('team_id', $team->id)
            ->where('config->enable_registration', true)
            ->get());

        return compact('periods');
    }

    public function getCourses(string $period)
    {
        $period = Period::query()
            ->where('uuid', $period)
            ->firstOrFail();

        $program = request()->query('program');

        $courses = CourseForGuestResource::collection(Course::query()
            ->with('batches')
            ->whereHas('division', function ($q) use ($period, $program) {
                $q->where('period_id', $period->id)
                    ->when($program, function ($q) use ($program) {
                        $q->whereHas('program', function ($q) use ($program) {
                            $q->where('uuid', $program);
                        });
                    });
            })
            ->where('enable_registration', true)
            ->get());

        return compact('courses');
    }

    public function getBatches(string $period, string $course)
    {
        $period = Period::query()
            ->where('uuid', $period)
            ->firstOrFail();

        $course = Course::query()
            ->where('uuid', $course)
            ->firstOrFail();

        $batches = Batch::query()
            ->where('course_id', $course->id)
            ->get()
            ->map(function ($batch) {
                return [
                    'uuid' => $batch->uuid,
                    'name' => $batch->name,
                ];
            });

        return compact('batches');
    }

    public function initiate(Request $request)
    {
        if ($request->registration && $request->existing_registration_with_pending_verification) {
            $registration = $request->registration;
            $registration->updateMeta([
                'email_otp' => rand(100000, 999999),
                'email_verification' => false,
                'contact_number_otp' => rand(100000, 999999),
                'contact_number_verification' => false,
            ]);

            $this->sendConfirmationEmail($registration);

            return $registration;
        }

        \DB::beginTransaction();

        $params = $request->all();
        $params['source'] = 'candidate';

        $contact = (new CreateContact)->execute($params);

        $registration = Registration::forceCreate([
            'contact_id' => $contact->id,
            'period_id' => $request->period_id,
            'course_id' => $request->course_id,
            'date' => today()->toDateString(),
            'fee' => $request->registration_fee,
            'payment_status' => $request->registration_fee ? PaymentStatus::UNPAID : PaymentStatus::NA,
            'is_online' => true,
            'status' => RegistrationStatus::INITIATED,
            'meta' => [
                'email_otp' => rand(100000, 999999),
                'email_verification' => false,
                'contact_number_otp' => rand(100000, 999999),
                'contact_number_verification' => false,
                'verification_token' => Str::random(32),
                'application_number' => strtoupper(date('Ymd').Str::random(8)),
            ],
        ]);

        \DB::commit();

        $this->sendConfirmationEmail($registration);

        $registration->setMediaToken($request);

        return $registration;
    }

    public function initiateMinimal(Request $request)
    {
        $batch = $request->batch ? Batch::query()
            ->with('course')
            ->where('uuid', $request->batch)
            ->firstOrFail() : null;

        $params = $request->all();
        $params['source'] = 'candidate';

        $contact = (new CreateContact)->execute($params);

        $registration = Registration::forceCreate([
            'contact_id' => $contact->id,
            'period_id' => $request->period_id,
            'course_id' => $request->course_id,
            'date' => today()->toDateString(),
            'fee' => $request->registration_fee,
            'payment_status' => $request->registration_fee ? PaymentStatus::UNPAID : PaymentStatus::NA,
            'is_online' => true,
            'meta' => [
                'batch' => $batch?->course?->name . ' ' . $batch?->name,
                'application_number' => strtoupper(date('Ymd').Str::random(8)),
            ],
        ]);

        $teamId = $contact->team_id;

        (new SetTeamWiseModuleConfig)->execute($teamId, 'student');

        $codeNumberDetail = $this->codeNumber($registration->course_id, $teamId);

        $registration->number_format = Arr::get($codeNumberDetail, 'number_format');
        $registration->number = Arr::get($codeNumberDetail, 'number');
        $registration->code_number = Arr::get($codeNumberDetail, 'code_number');
        $registration->status = RegistrationStatus::PENDING;
        $registration->setMeta([
            'media_token' => (string) Str::uuid(),
        ]);
        $registration->save();

        \DB::commit();

        return $registration;
    }

    private function sendConfirmationEmail(Registration $registration)
    {
        if (empty($registration->contact?->email)) {
            return;
        }

        (new SendMailTemplate)->execute(
            email: $registration->contact->email,
            code: 'online-registration-email-confirmation',
            variables: [
                'name' => $registration->contact->name,
                'otp' => $registration->getMeta('email_otp'),
                'application_number' => $registration->getMeta('application_number'),
                'program' => $registration->course?->division?->program?->name,
                'period' => $registration->period->name,
                'course' => $registration->course->name,
            ]
        );
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'code' => 'required',
        ]);

        $registration = Registration::query()
            ->where('meta->verification_token', $request->token)
            ->where('status', RegistrationStatus::INITIATED)
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($registration->getMeta('email_otp') != $request->code) {
            throw ValidationException::withMessages(['code' => trans('student.online_registration.invalid_otp')]);
        }

        $registration->update([
            'meta->auth_token' => Str::random(32),
            'meta->auth_token_expiry' => now()->addMinutes(60)->toDateTimeString(),
            'meta->email_verification' => true,
        ]);

        (new SendMailTemplate)->execute(
            email: $registration->contact->email,
            code: 'online-registration-email-confirmed',
            variables: [
                'name' => $registration->contact->name,
                'program' => $registration->course?->division?->program?->name,
                'period' => $registration->period->name,
                'course' => $registration->course->name,
                'application_number' => $registration->getMeta('application_number'),
            ]
        );

        return $registration;
    }

    public function find(Request $request)
    {
        $request->validate([
            'application_number' => 'required|min:4|max:50',
            'email' => 'required|email',
        ]);

        $registration = Registration::query()
            ->where('meta->application_number', $request->application_number)
            ->whereHas('contact', function ($q) use ($request) {
                $q->whereEmail($request->email);
            })
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages(['application_number' => trans('general.errors.invalid_input')]);
        }

        $registration->update([
            'meta->email_otp' => rand(100000, 999999),
            'meta->email_otp_lifetime' => now()->addMinutes(10)->toDateTimeString(),
        ]);

        (new SendMailTemplate)->execute(
            email: $registration->contact->email,
            code: 'send-email-otp',
            variables: [
                'name' => $registration->contact->name,
                'code' => $registration->getMeta('email_otp'),
                'token_lifetime' => 10,
            ]
        );
    }

    public function verify(Request $request)
    {
        $request->validate([
            'application_number' => 'required|min:4|max:50',
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $registration = Registration::query()
            ->where('meta->application_number', $request->application_number)
            ->whereHas('contact', function ($q) use ($request) {
                $q->whereEmail($request->email);
            })
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages(['application_number' => trans('general.errors.invalid_input')]);
        }

        if ($registration->getMeta('email_otp') != $request->code) {
            throw ValidationException::withMessages(['code' => trans('general.errors.invalid_input')]);
        }

        if ($registration->getMeta('email_otp_lifetime') < now()->toDateTimeString()) {
            throw ValidationException::withMessages(['code' => trans('general.errors.invalid_input')]);
        }

        $registration->update([
            'meta->auth_token' => Str::random(32),
            'meta->auth_token_expiry' => now()->addMinutes(60)->toDateTimeString(),
        ]);

        return $registration;
    }

    public function findByUuidOrFail(Request $request, string $applicationNumber)
    {
        $authToken = $request->header('auth-token');

        $registration = Registration::query()
            ->where('meta->application_number', $applicationNumber)
            ->where('meta->auth_token', $authToken)
            ->firstOrFail();

        if ($registration->getMeta('auth_token_expiry') < now()->toDateTimeString()) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('student.online_registration.application')])]);
        }

        $registration->load(['contact', 'contact.caste', 'contact.category', 'contact.religion', 'course.division.program', 'period', 'course', 'transactions' => function ($q) {
            $q->withPayment();
        }]);

        return $registration;
    }

    public function isDownloadable(Registration $registration)
    {
        if ($registration->status == RegistrationStatus::INITIATED) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function updateBasic(Request $request, Registration $registration)
    {
        $team = $registration->period->team;

        $category = $request->category ? Option::query()
            ->byTeam($team->id)
            ->where('type', OptionType::MEMBER_CATEGORY->value)
            ->where('uuid', $request->category)
            ->getOrFail(trans('contact.category.category'), 'category') : null;

        $caste = $request->caste ? Option::query()
            ->byTeam($team->id)
            ->where('type', OptionType::MEMBER_CASTE->value)
            ->where('uuid', $request->caste)
            ->getOrFail(trans('contact.caste.caste'), 'caste') : null;

        $religion = $request->religion ? Option::query()
            ->byTeam($team->id)
            ->where('type', OptionType::RELIGION->value)
            ->where('uuid', $request->religion)
            ->getOrFail(trans('contact.religion.religion'), 'religion') : null;

        $contact = $registration->contact;
        $contact->father_name = $request->father_name;
        $contact->mother_name = $request->mother_name;
        $contact->gender = $request->gender;
        $contact->birth_date = $request->birth_date;
        $contact->anniversary_date = $request->anniversary_date ?? null;
        $contact->birth_place = $request->birth_place;
        $contact->nationality = $request->nationality;
        $contact->mother_tongue = $request->mother_tongue;
        $contact->blood_group = $request->blood_group;
        $contact->marital_status = $request->marital_status;
        $contact->unique_id_number1 = $request->unique_id_number1;
        $contact->unique_id_number2 = $request->unique_id_number2;
        $contact->unique_id_number3 = $request->unique_id_number3;
        $contact->unique_id_number4 = $request->unique_id_number4;
        $contact->unique_id_number5 = $request->unique_id_number5;
        $contact->category_id = $category?->id;
        $contact->caste_id = $caste?->id;
        $contact->religion_id = $religion?->id;
        $contact->save();

        $registration->setMeta([
            'custom_fields' => $request->custom_fields,
        ]);
        $registration->setConfig(['basic_updated' => true]);
        $registration->save();

        $registration->updateMedia($request);
    }

    public function updateContact(Request $request, Registration $registration)
    {
        if ($registration->getConfig('basic_updated') != true) {
            throw ValidationException::withMessages(['message' => trans('student.online_registration.basic_info_required')]);
        }

        $contact = $registration->contact;
        $contact->alternate_records = [
            'contact_number' => $request->input('alternate_records.contact_number'),
            'email' => $request->input('alternate_records.email'),
        ];
        $contact->address = [
            'present' => [
                'address_line1' => $request->input('present_address.address_line1'),
                'address_line2' => $request->input('present_address.address_line2'),
                'city' => $request->input('present_address.city'),
                'state' => $request->input('present_address.state'),
                'zipcode' => $request->input('present_address.zipcode'),
                'country' => $request->input('present_address.country'),
            ],
            'permanent' => $request->input('permanent_address.same_as_present_address') ? [
                'same_as_present_address' => true,
                'address_line1' => $request->input('present_address.address_line1'),
                'address_line2' => $request->input('present_address.address_line2'),
                'city' => $request->input('present_address.city'),
                'state' => $request->input('present_address.state'),
                'zipcode' => $request->input('present_address.zipcode'),
                'country' => $request->input('present_address.country'),
            ] : [
                'same_as_present_address' => false,
                'address_line1' => $request->input('permanent_address.address_line1'),
                'address_line2' => $request->input('permanent_address.address_line2'),
                'city' => $request->input('permanent_address.city'),
                'state' => $request->input('permanent_address.state'),
                'zipcode' => $request->input('permanent_address.zipcode'),
                'country' => $request->input('permanent_address.country'),
            ],
        ];

        $contact->save();

        $registration->setConfig(['contact_updated' => true]);
        $registration->save();

        $registration->updateMedia($request);
    }

    public function uploadFile(Request $request, Registration $registration)
    {
        $registration->updateMedia($request);

        $registration->setConfig(['file_uploaded' => true]);
        $registration->save();
    }

    public function updateReview(Request $request, Registration $registration)
    {
        if ($registration->getConfig('file_uploaded') != true) {
            throw ValidationException::withMessages(['message' => trans('student.online_registration.upload_file_required')]);
        }

        $teamId = $registration->period->team_id;

        (new SetTeamWiseModuleConfig)->execute($teamId, 'student');

        $codeNumberDetail = $this->codeNumber($registration->course_id, $teamId);

        $registration->number_format = Arr::get($codeNumberDetail, 'number_format');
        $registration->number = Arr::get($codeNumberDetail, 'number');
        $registration->code_number = Arr::get($codeNumberDetail, 'code_number');
        $registration->status = RegistrationStatus::PENDING;
        $registration->save();

        $registration->updateMedia($request);

        (new SendMailTemplate)->execute(
            email: $registration->contact->email,
            code: 'online-registration-submitted',
            variables: [
                'name' => $registration->contact->name,
                'application_number' => $registration->getMeta('application_number'),
                'program' => $registration->course?->division?->program?->name,
                'period' => $registration->period->name,
                'course' => $registration->course->name,
            ]
        );
    }

    public function photoUploaded(Registration $registration)
    {
        $registration->setConfig(['avatar_uploaded' => true]);
        $registration->save();
    }

    public function photoRemoved(Registration $registration)
    {
        $registration->setConfig(['avatar_uploaded' => false]);
        $registration->save();
    }
}
