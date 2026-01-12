<?php

namespace App\Http\Requests\Student;

use App\Concerns\Auth\EnsureUniqueUserEmail;
use App\Enums\Finance\PaymentStatus;
use App\Enums\OptionType;
use App\Enums\Student\RegistrationStatus;
use App\Enums\Transport\Direction;
use App\Domain\Academic\Models\Batch;
use App\Models\Finance\FeeConcession;
use App\Models\Option;
use App\Models\Transport\Circle;
use App\Rules\StrongPassword;
use App\Rules\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class RegistrationActionRequest extends FormRequest
{
    use EnsureUniqueUserEmail;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'status' => ['required', new Enum(RegistrationStatus::class)],
            'rejection_remarks' => 'nullable|required_if:status,rejected|min:20|max:100',
            'date' => 'nullable|required_if:status,approved|date',
            'fee_concession' => 'nullable|uuid',
            'enrollment_type' => 'nullable|uuid',
            'transport_circle' => 'nullable|uuid',
            'elective_subjects' => 'nullable|array',
            'groups' => 'nullable|array',
            'direction' => ['nullable', new Enum(Direction::class)],
        ];

        if ($this->status == RegistrationStatus::APPROVED->value && ! $this->is_provisional) {
            $rules['code_number'] = 'required';
        }

        if ($this->create_user_account) {
            $rules['email'] = 'required|email';
            $rules['username'] = ['required', new Username, Rule::unique('users')];
            $rules['password'] = ['required', 'same:password_confirmation', new StrongPassword];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $registration = $this->route('registration');

            if (! config('config.student.enable_provisional_admission') && $this->is_provisional) {
                $validator->errors()->add('message', trans('general.errors.invalid_action'));
            }

            if ($registration->status != RegistrationStatus::PENDING) {
                $validator->errors()->add('status', trans('general.errors.invalid_action'));
            }

            if ($this->status == RegistrationStatus::INITIATED->value) {
                return;
            }

            if ($this->status == RegistrationStatus::REJECTED->value) {
                return;
            }

            $enrollmentType = $this->enrollment_type ? Option::query()
                ->whereType(OptionType::STUDENT_ENROLLMENT_TYPE->value)
                ->whereUuid($this->enrollment_type)
                ->getOrFail(trans('student.enrollment_type.enrollment_type')) : null;

            $batch = Batch::query()
                ->byPeriod()
                ->filterAccessible()
                ->whereCourseId($registration->course_id)
                ->whereUuid($this->batch)
                ->getOrFail(trans('academic.batch.batch'), 'batch');

            if ($this->date < $registration->date->value) {
                $validator->errors()->add('date', trans('validation.after_or_equal', ['attribute' => trans('student.admission.props.date'), 'date' => $registration->date->formatted]));
            }

            if ($registration->fee->value > 0 && $registration->payment_status != PaymentStatus::PAID) {
                throw ValidationException::withMessages(['message' => trans('student.registration.fee_unpaid')]);
            }

            $feeConcession = null;
            $transportCircle = null;
            if ($this->status == RegistrationStatus::APPROVED->value) {
                $feeConcession = $this->fee_concession ? FeeConcession::query()
                    ->byPeriod($registration->period_id)
                    ->whereUuid($this->fee_concession)
                    ->getOrFail(trans('finance.fee_concession.fee_concession')) : null;

                $transportCircle = $this->transport_circle ? Circle::query()
                    ->byPeriod($registration->period_id)
                    ->whereUuid($this->transport_circle)
                    ->getOrFail(trans('transport.circle.circle')) : null;

                if ($this->transport_circle && empty($this->direction)) {
                    throw ValidationException::withMessages(['direction' => trans('validation.required', ['attribute' => trans('transport.circle.direction')])]);
                }
            }

            if ($this->create_user_account) {
                $contact = $registration->contact;
                $this->ensureEmailDoesntBelongToOtherContact($contact, $this->email);

                $this->ensureEmailDoesntBelongToUserContact($this->email);
            }

            $this->merge([
                'batch_id' => $batch?->id,
                'enrollment_type_id' => $enrollmentType?->id,
                'fee_concession' => $feeConcession,
                'transport_circle' => $transportCircle,
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
            'status' => __('student.registration.props.status'),
            'rejection_remarks' => __('student.registration.props.rejection_remarks'),
            'date' => __('student.admission.props.date'),
            'batch' => __('academic.batch.batch'),
            'enrollment_type' => __('student.enrollment_type.enrollment_type'),
            'elective_subjects' => __('academic.subject.elective_subject'),
            'groups' => __('student.group.group'),
            'code_number' => __('student.admission.props.code_number'),
            'provisional_code_number' => __('student.admission.props.provisional_code_number'),
            'email' => __('contact.login.props.email'),
            'username' => __('contact.login.props.username'),
            'password' => __('contact.login.props.password'),
            'password_confirmation' => __('contact.login.props.password_confirmation'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'rejection_remarks.required_if' => __('validation.required', ['attribute' => __('student.registration.props.rejection_remarks')]),
            'date.required_if' => __('validation.required', ['attribute' => __('student.admission.props.date')]),
            'batch.required_if' => __('validation.required', ['attribute' => __('academic.batch.batch')]),
            'code_number.required_if' => __('validation.required', ['attribute' => __('student.admission.props.code_number')]),
            'email.required_if' => __('validation.required', ['attribute' => __('contact.login.props.email')]),
            'username.required_if' => __('validation.required', ['attribute' => __('contact.login.props.username')]),
            'password.required_if' => __('validation.required', ['attribute' => __('contact.login.props.password')]),
        ];
    }
}
