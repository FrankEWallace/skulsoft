<?php

namespace App\Http\Requests\Form;

use App\Enums\CustomFieldType;
use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Models\Form\Form;
use App\Support\HasAudience;
use Illuminate\Foundation\Http\FormRequest as HttpFormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class FormRequest extends HttpFormRequest
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
            'name' => 'required|max:255',
            'due_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'summary' => 'required|min:5|max:1000',
            'description' => 'nullable|max:10000',
            'student_audience_type' => [new Enum(StudentAudienceType::class)],
            'employee_audience_type' => [new Enum(EmployeeAudienceType::class)],
            'student_audiences' => 'array|required_if:student_audience_type,division_wise,course_wise,batch_wise',
            'employee_audiences' => 'array|required_if:employee_audience_type,department_wise,designation_wise',
            'fields' => ['required', 'array'],
            'fields.*.uuid' => ['required', 'uuid', 'distinct'],
            'fields.*.type' => ['required', new Enum(CustomFieldType::class)],
            'fields.*.label' => ['nullable', 'required_unless:fields.*.type,paragraph', 'max:100', 'distinct'],
            'fields.*.name' => ['nullable', 'required_if:fields.*.type,camera_image,file_upload', 'distinct'],
            'fields.*.content' => ['required_if:fields.*.type,paragraph', 'max:1000'],
            'fields.*.min_length' => ['nullable', 'required_if:fields.*.type,text_input,multi_line_text_input', 'numeric', 'min:0', 'max:100000000'],
            'fields.*.max_length' => ['nullable', 'required_if:fields.*.type,text_input,multi_line_text_input', 'numeric', 'min:0', 'max:100000000', 'gte:fields.*.min_length'],
            'fields.*.min_value' => ['nullable', 'required_if:fields.*.type,number_input,currency_input', 'numeric', 'min:0', 'max:100000000'],
            'fields.*.max_value' => ['nullable', 'required_if:fields.*.type,number_input,currency_input', 'numeric', 'min:0', 'max:100000000', 'gte:fields.*.min_value'],
            'fields.*.is_required' => ['boolean'],
            'fields.*.options' => ['required_if:fields.*.type,select_input,checkbox_input,radio_input'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Form)->getModelName();

            $formUuid = $this->route('form');

            $existingForm = Form::query()
                ->whereName($this->name)
                ->when($formUuid, function ($query) use ($formUuid) {
                    return $query->where('uuid', '!=', $formUuid);
                })
                ->exists();

            if ($existingForm) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => __('form.props.name')]));
            }

            $data = $this->validateInput($this->all());

            $newFields = collect($this->fields)->map(function ($field) {
                return collect($field)->only(['uuid', 'name', 'label', 'type', 'min_length', 'max_length', 'min_value', 'max_value', 'is_required', 'options', 'content']);
            });

            $this->merge([
                'student_audience_type' => Arr::get($data, 'studentAudienceType'),
                'employee_audience_type' => Arr::get($data, 'employeeAudienceType'),
                'student_audiences' => Arr::get($data, 'studentAudiences'),
                'employee_audiences' => Arr::get($data, 'employeeAudiences'),
                'fields' => $newFields->toArray(),
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
            'student_audience_type' => __('communication.email.props.audience'),
            'employee_audience_type' => __('communication.email.props.audience'),
            'student_audiences' => __('communication.email.props.audience'),
            'employee_audiences' => __('communication.email.props.audience'),
            'fields.*.type' => __('custom_field.props.type'),
            'fields.*.name' => __('custom_field.props.name'),
            'fields.*.label' => __('custom_field.props.label'),
            'fields.*.content' => __('custom_field.props.content'),
            'fields.*.min_length' => __('custom_field.props.min_length'),
            'fields.*.max_length' => __('custom_field.props.max_length'),
            'fields.*.min_value' => __('custom_field.props.min_value'),
            'fields.*.max_value' => __('custom_field.props.max_value'),
            'fields.*.options' => __('custom_field.props.options'),
            'fields.*.is_required' => __('custom_field.props.is_required'),
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
            'fields.*.name.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.name')]),
            'fields.*.name.required_unless' => trans('validation.required', ['attribute' => trans('custom_field.props.name')]),
            'fields.*.label.required_unless' => trans('validation.required', ['attribute' => trans('custom_field.props.label')]),
            'fields.*.content.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.content')]),
            'fields.*.min_length.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.min_length')]),
            'fields.*.max_length.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.max_length')]),
            'fields.*.min_value.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.min_value')]),
            'fields.*.max_value.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.max_value')]),
            'fields.*.options.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.options')]),
        ];
    }
}
