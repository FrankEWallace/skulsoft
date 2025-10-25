<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\CertificateFor;
use App\Enums\Academic\CertificateType;
use App\Enums\CustomFieldType;
use App\Models\Academic\CertificateTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CertificateTemplateRequest extends FormRequest
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
            'name' => ['required', 'max:200'],
            'type' => ['required', new Enum(CertificateType::class)],
            'for.value' => ['required', new Enum(CertificateFor::class)],
            'number_prefix' => 'nullable|max:30',
            'number_digit' => 'required|min:1|max:10',
            'number_suffix' => 'nullable|max:30',
            'custom_fields' => ['required', 'array'],
            'custom_fields.*.uuid' => ['required', 'uuid', 'distinct'],
            'custom_fields.*.type' => ['required', Rule::enum(CustomFieldType::class)->except([])],
            'custom_fields.*.name' => ['required', 'min:2', 'max:100', 'distinct', 'regex:/^[A-Z_]+$/'],
            'custom_fields.*.label' => ['required_unless:custom_fields.*.type,paragraph', 'min:2', 'max:100'],
            'custom_fields.*.min_length' => ['required_if:custom_fields.*.type,text_input,multi_line_text_input', 'numeric', 'min:0', 'max:1000'],
            'custom_fields.*.max_length' => ['required_if:custom_fields.*.type,text_input,multi_line_text_input', 'numeric', 'min:0', 'max:1000', 'gte:custom_fields.*.min_length'],
            'custom_fields.*.min_value' => ['required_if:custom_fields.*.type,number_input,currency_input', 'numeric', 'min:0', 'max:1000'],
            'custom_fields.*.max_value' => ['required_if:custom_fields.*.type,number_input,currency_input', 'numeric', 'min:0', 'max:1000', 'gte:custom_fields.*.min_value'],
            'custom_fields.*.is_required' => ['boolean'],
            'custom_fields.*.options' => ['required_if:custom_fields.*.type,select_input,checkbox_input,radio_input'],
            'custom_template_file_name' => 'required_if:has_custom_template_file,1|max:255',
            'content' => ['required_if:has_custom_template_file,0', 'max:10000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('certificate_template');

            $existingTemplate = CertificateTemplate::query()
                ->byTeam()
                ->where('uuid', '!=', $uuid)
                ->where('name', $this->name)
                ->where('for', $this->for)
                ->exists();

            if ($existingTemplate) {
                $validator->errors()->add('name', trans('global.duplicate', ['attribute' => __('academic.certificate.template.template')]));
            }

            $newCustomFields = collect($this->custom_fields)->map(function ($customField) {
                return collect($customField)->only(['uuid', 'name', 'label', 'type', 'min_length', 'max_length', 'min_value', 'max_value', 'show_label', 'is_required', 'options']);
            });

            $this->merge([
                'for' => $this->for['value'],
                'custom_fields' => $newCustomFields->toArray(),
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
            'name' => __('academic.certificate.template.props.name'),
            'type' => __('academic.certificate.template.props.type'),
            'for' => __('academic.certificate.template.props.for'),
            'number_prefix' => __('academic.certificate.template.props.number_prefix'),
            'number_digit' => __('academic.certificate.template.props.number_digit'),
            'number_suffix' => __('academic.certificate.template.props.number_suffix'),
            'custom_fields.*.type' => __('custom_field.props.type'),
            'custom_fields.*.name' => __('custom_field.props.name'),
            'custom_fields.*.label' => __('custom_field.props.label'),
            'custom_fields.*.min_length' => __('custom_field.props.min_length'),
            'custom_fields.*.max_length' => __('custom_field.props.max_length'),
            'custom_fields.*.min_value' => __('custom_field.props.min_value'),
            'custom_fields.*.max_value' => __('custom_field.props.max_value'),
            'custom_fields.*.options' => __('custom_field.props.options'),
            'custom_fields.*.is_required' => __('custom_field.props.is_required'),
            'custom_template_file_name' => __('academic.certificate.template.props.custom_template_file_name'),
            'content' => __('academic.certificate.template.props.content'),
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
            'custom_fields.*.min_length.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.min_length')]),
            'custom_fields.*.max_length.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.max_length')]),
            'custom_fields.*.min_value.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.min_value')]),
            'custom_fields.*.max_value.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.max_value')]),
            'custom_fields.*.options.required_if' => trans('validation.required', ['attribute' => trans('custom_field.props.options')]),
            'custom_template_file_name.required_if' => trans('validation.required', ['attribute' => trans('academic.certificate.template.props.custom_template_file_name')]),
            'content.required_if' => trans('validation.required', ['attribute' => trans('academic.certificate.template.props.content')]),
        ];
    }
}
