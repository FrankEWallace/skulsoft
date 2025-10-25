<?php

namespace App\Http\Requests\Student;

use App\Enums\OptionType;
use App\Enums\QualificationResult;
use App\Models\Contact;
use App\Models\Option;
use App\Models\Qualification;
use App\Models\Student\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegistrationQualificationRequest extends FormRequest
{
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
        return [
            'level' => 'required',
            'course' => 'required|min:2|max:100',
            'session' => 'nullable|min:2|max:100',
            'institute' => 'nullable|min:2|max:100',
            'institute_address' => 'nullable|min:2|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'affiliated_to' => 'nullable|min:2|max:100',
            'result' => ['nullable', new Enum(QualificationResult::class)],
            'total_marks' => 'required_if:result,pass|numeric|min:0|max:10000',
            'obtained_marks' => 'required_if:result,pass|numeric|min:0|max:10000|lte:total_marks',
            'failed_subjects' => 'required_if:result,reappear|min:2|max:200',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $registrationUuid = $this->route('registration');
            $qualificationUuid = $this->route('qualification');

            $registration = Registration::query()
                ->whereUuid($registrationUuid)
                ->firstOrFail();

            $qualificationLevel = Option::query()
                ->byTeam()
                ->whereType(OptionType::QUALIFICATION_LEVEL->value)
                ->whereUuid($this->level)
                ->getOrFail(__('student.qualification_level.qualification_level'), 'level');

            $existingQualification = Qualification::whereHasMorph(
                'model', [Contact::class],
                function ($q) use ($registration) {
                    $q->whereId($registration->contact_id);
                }
            )
                ->when($qualificationUuid, function ($q, $qualificationUuid) {
                    $q->where('uuid', '!=', $qualificationUuid);
                })
                ->whereCourse($this->course)
                ->exists();

            if ($existingQualification) {
                $validator->errors()->add('course', trans('validation.unique', ['attribute' => __('student.qualification.props.course')]));
            }

            $this->merge([
                'level_id' => $qualificationLevel->id,
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
            'course' => __('student.qualification.props.course'),
            'session' => __('student.qualification.props.session'),
            'institute' => __('student.qualification.props.institute'),
            'institute_address' => __('student.qualification.props.institute_address'),
            'start_date' => __('student.qualification.props.start_date'),
            'end_date' => __('student.qualification.props.end_date'),
            'affiliated_to' => __('student.qualification.props.affiliated_to'),
            'result' => __('student.qualification.props.result'),
            'total_marks' => __('student.qualification.props.total_marks'),
            'obtained_marks' => __('student.qualification.props.obtained_marks'),
            'failed_subjects' => __('student.qualification.props.failed_subjects'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
