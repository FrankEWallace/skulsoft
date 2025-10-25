<?php

namespace App\Http\Requests;

use App\Enums\CustomFieldForm;
use App\Enums\CustomFieldType;
use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CustomFieldRequest extends FormRequest
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
            'form' => ['required', new Enum(CustomFieldForm::class)],
            'type' => ['required', new Enum(CustomFieldType::class)],
            'label' => ['required', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'min_length' => ['required_if:type,text_input,multi_line_text_input', 'integer', 'min:0'],
            'max_length' => ['required_if:type,text_input,multi_line_text_input', 'integer', 'min:0', 'gte:min_length'],
            'min_value' => ['required_if:type,number_input,currency_input', 'integer', 'min:0'],
            'max_value' => ['required_if:type,number_input,currency_input', 'integer', 'min:0', 'gte:min_value'],
            'options' => ['required_if:type,select_input,multi_select_input,radio_input,checkbox_input'],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $customFieldForm = $this->route('custom_field');

            $existingField = CustomField::query()
                ->whereLabel($this->label)
                ->whereForm($this->form)
                ->when($customFieldForm, function ($query) use ($customFieldForm) {
                    return $query->where('uuid', '!=', $customFieldForm);
                })
                ->exists();

            if ($existingField) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => __('custom_field.props.label')]));
            }

            if (in_array($this->type, ['paragraph', 'camera_image', 'file_upload'])) {
                $validator->errors()->add('type', __('validation.in', ['attribute' => __('custom_field.props.type')]));
            }
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
            'form' => __('custom_field.props.form'),
            'type' => __('custom_field.props.type'),
            'label' => __('custom_field.props.label'),
            'is_required' => __('custom_field.props.is_required'),
            'position' => __('custom_field.props.position'),
            'min_length' => __('custom_field.props.min_length'),
            'max_length' => __('custom_field.props.max_length'),
            'min_value' => __('custom_field.props.min_value'),
            'max_value' => __('custom_field.props.max_value'),
            'options' => __('custom_field.props.options'),
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
            'min_length.required_if' => __('validation.required', ['attribute' => __('custom_field.props.min_length')]),
            'max_length.required_if' => __('validation.required', ['attribute' => __('custom_field.props.max_length')]),
            'min_value.required_if' => __('validation.required', ['attribute' => __('custom_field.props.min_value')]),
            'max_value.required_if' => __('validation.required', ['attribute' => __('custom_field.props.max_value')]),
            'options.required_if' => __('validation.required', ['attribute' => __('custom_field.props.options')]),
        ];
    }
}
