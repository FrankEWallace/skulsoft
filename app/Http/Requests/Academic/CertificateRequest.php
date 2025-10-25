<?php

namespace App\Http\Requests\Academic;

use App\Concerns\CustomFieldValidation;
use App\Models\Academic\Certificate;
use App\Models\Academic\CertificateTemplate;
use App\Models\Employee\Employee;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;

class CertificateRequest extends FormRequest
{
    use CustomFieldValidation;

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
            'template' => 'required|uuid',
            'student' => 'nullable|uuid',
            'employee' => 'nullable|uuid',
            'name' => 'nullable|min:1|max:255',
            'date' => 'required|date_format:Y-m-d',
            'is_duplicate' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('certificate');

            $template = CertificateTemplate::query()
                ->byTeam()
                ->where('uuid', $this->template)
                ->getOrFail(__('academic.certificate.template.template_not_found'), 'template');

            $customFields = $this->validateFields($validator, $template->detailed_custom_fields);

            $modelType = null;
            $model = null;

            if ($template->for->value == 'student') {
                $modelType = 'Student';
            } elseif ($template->for->value == 'employee') {
                $modelType = 'Employee';
            }

            if ($modelType == 'Student' && $this->student) {
                $model = Student::query()
                    ->byTeam()
                    ->whereUuid($this->student)
                    ->getOrFail(__('student.student'), 'student');
            } elseif ($modelType == 'Employee' && $this->employee) {
                $model = Employee::query()
                    ->byTeam()
                    ->whereUuid($this->employee)
                    ->getOrFail(__('employee.employee'), 'employee');
            } else {
                if (empty($this->name)) {
                    $validator->errors()->add('name', __('validation.required', ['attribute' => trans('contact.props.name')]));
                }
            }

            if (empty($model)) {
                $modelType = null;
            }

            $existingCertificate = Certificate::query()
                ->when($uuid, function ($query) use ($uuid) {
                    return $query->where('uuid', '!=', $uuid);
                })
                ->where('template_id', $template->id)
                ->where('model_type', $modelType)
                ->where('model_id', $model?->id)
                ->where('date', $this->date)
                ->when(empty($modelType), function ($query) {
                    return $query->where('meta->name', $this->name);
                })
                ->exists();

            if ($existingCertificate && ! $this->is_duplicate) {
                $validator->errors()->add('certificate', __('academic.certificate.already_exists'));
            }

            $this->merge([
                'model_type' => $modelType,
                'model' => $model,
                'template' => $template,
                'custom_fields' => $customFields,
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
            'template' => trans('academic.certificate.template.template'),
            'student' => trans('student.student'),
            'employee' => trans('employee.employee'),
            'date' => trans('academic.certificate.props.date'),
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
            //
        ];
    }
}
