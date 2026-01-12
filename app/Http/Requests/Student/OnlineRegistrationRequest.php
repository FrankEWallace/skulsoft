<?php

namespace App\Http\Requests\Student;

use App\Enums\Student\RegistrationStatus;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\Program;
use App\Models\Student\Registration;
use App\Models\Team;
use App\Rules\AlphaSpace;
use Illuminate\Foundation\Http\FormRequest;

class OnlineRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'team' => 'required|uuid',
            'program' => 'required|uuid',
            'period' => 'required|uuid',
            'course' => 'required|uuid',
            'batch' => 'nullable|uuid',
            'first_name' => ['required', 'min:2', 'max:100', new AlphaSpace],
            'middle_name' => ['nullable', 'max:100', new AlphaSpace],
            'third_name' => ['nullable', 'max:100', new AlphaSpace],
            'last_name' => ['nullable', 'max:100', new AlphaSpace],
            'gender' => 'sometimes|required',
            'birth_date' => 'sometimes|required|date_format:Y-m-d',
            'contact_number' => 'required|min:10|max:20',
            'email' => 'required|email|max:100',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $team = Team::query()
                ->whereUuid($this->team)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('team.team')]), 'team');

            $program = Program::query()
                ->byTeam($team->id)
                ->whereUuid($this->program)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('academic.program.program')]), 'program');

            if (! $program->getConfig('enable_registration')) {
                $validator->errors()->add('program', trans('academic.program.registration_disabled_info'));
            }

            $period = Period::query()
                ->whereTeamId($team->id)
                ->whereUuid($this->period)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('academic.period.period')]), 'period');

            if (! $period->getConfig('enable_registration')) {
                $validator->errors()->add('period', trans('academic.period.registration_disabled_info'));
            }

            $course = Course::query()
                ->byPeriod($period->id)
                ->whereUuid($this->course)
                ->getOrFail(trans('validation.exists', ['attribute' => trans('academic.course.course')]), 'course');

            if (! $course->enable_registration) {
                $validator->errors()->add('course', trans('academic.course.registration_disabled_info'));
            }

            if (! $course->batches->count()) {
                $validator->errors()->add('course', trans('student.online_registration.no_batches_available'));
            }

            $existingRegistration = Registration::query()
                ->select('registrations.*')
                ->join('contacts', 'contacts.id', '=', 'registrations.contact_id')
                ->where(function ($q) {
                    $q->where('contacts.contact_number', $this->contact_number)
                        ->orWhere('contacts.email', $this->email);
                })
                ->where('registrations.course_id', $course->id)
                ->first();

            if ($existingRegistration && $existingRegistration->status == RegistrationStatus::PENDING) {
                $validator->errors()->add('message', trans('student.online_registration.pending_application_exists'));
            }

            $existingRegistrationWithPendingVerification = false;
            if ($existingRegistration && $existingRegistration->status == RegistrationStatus::INITIATED) {
                if ($existingRegistration->getMeta('email_verification')) {
                    $validator->errors()->add('message', trans('student.online_registration.pending_application_exists'));
                    $existingRegistration = null;
                } else {
                    $existingRegistrationWithPendingVerification = true;
                }
            }

            $this->merge([
                'team_id' => $team->id,
                'program_id' => $program?->id,
                'period_id' => $period?->id,
                'course_id' => $course?->id,
                'registration_fee' => $course->enable_registration ? $course->registration_fee->value : 0,
                'registration' => $existingRegistration,
                'existing_registration_with_pending_verification' => $existingRegistrationWithPendingVerification,
            ]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'team' => __('team.team'),
            'program' => __('academic.program.program'),
            'period' => __('academic.period.period'),
            'course' => __('academic.course.course'),
            'batch' => __('academic.batch.batch'),
            'first_name' => __('contact.props.first_name'),
            'middle_name' => __('contact.props.middle_name'),
            'third_name' => __('contact.props.third_name'),
            'last_name' => __('contact.props.last_name'),
            'gender' => __('contact.props.gender'),
            'birth_date' => __('contact.props.birth_date'),
            'contact_number' => __('contact.props.contact_number'),
            'email' => __('contact.props.email'),
        ];
    }
}
