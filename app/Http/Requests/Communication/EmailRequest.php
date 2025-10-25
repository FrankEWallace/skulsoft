<?php

namespace App\Http\Requests\Communication;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Models\Communication\Announcement;
use App\Support\HasAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class EmailRequest extends FormRequest
{
    use HasAudience;

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
            'subject' => 'required|max:255',
            'student_audience_type' => [new Enum(StudentAudienceType::class)],
            'employee_audience_type' => [new Enum(EmployeeAudienceType::class)],
            'student_audiences' => 'array|required_if:student_audience_type,division_wise,course_wise,batch_wise',
            'employee_audiences' => 'array|required_if:employee_audience_type,department_wise,designation_wise',
            'inclusion' => 'nullable|max:10000',
            'exclusion' => 'nullable|max:10000',
            'content' => 'nullable|min:2|max:10000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Announcement)->getModelName();

            $communicationUuid = $this->route('communication');

            $data = $this->validateInput($this->all());

            $inclusion = explode("\n", $this->inclusion);
            $inclusion = array_filter($inclusion, 'trim');

            $exclusion = explode("\n", $this->exclusion);
            $exclusion = array_filter($exclusion, 'trim');

            $this->merge([
                'student_audience_type' => Arr::get($data, 'studentAudienceType'),
                'employee_audience_type' => Arr::get($data, 'employeeAudienceType'),
                'student_audiences' => Arr::get($data, 'studentAudiences'),
                'employee_audiences' => Arr::get($data, 'employeeAudiences'),
                'inclusion' => $inclusion,
                'exclusion' => $exclusion,
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
            'subject' => __('communication.email.props.subject'),
            'student_audience_type' => __('communication.email.props.audience'),
            'employee_audience_type' => __('communication.email.props.audience'),
            'student_audiences' => __('communication.email.props.audience'),
            'employee_audiences' => __('communication.email.props.audience'),
            'content' => __('communication.email.props.content'),
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
            'student_audiences.required_if' => __('validation.required', ['attribute' => trans('communication.email.props.audience')]),
            'employee_audiences.required_if' => __('validation.required', ['attribute' => trans('communication.email.props.audience')]),
        ];
    }
}
