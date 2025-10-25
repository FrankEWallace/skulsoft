<?php

namespace App\Http\Requests\Form;

use App\Concerns\CustomFieldValidation;
use App\Enums\CustomFieldType;
use App\Models\Form\Form;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class FormSubmitRequest extends FormRequest
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
            'fields' => 'array|min:1',
            'fields.*.type' => ['required', new Enum(CustomFieldType::class)],
            'fields.*.uuid' => ['required', 'uuid', 'distinct'],
            'fields.*.label' => ['required', 'required_unless:fields.*.type,paragraph', 'max:50', 'distinct'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('form');

            $form = Form::findByUuidOrFail($uuid);

            if ($form->due_date->value < today()->toDateString()) {
                throw ValidationException::withMessages(['message' => trans('form.could_not_submit_expired_form')]);
            }

            $fields = $this->validateFields($validator, $form->fields, 'fields');

            $this->merge([
                'fields' => $fields,
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
            'fields' => __('form.props.fields'),
            'fields.*.label' => __('custom_field.props.label'),
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
