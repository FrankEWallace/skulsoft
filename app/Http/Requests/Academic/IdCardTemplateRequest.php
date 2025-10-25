<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\IdCardFor;
use App\Models\Academic\IdCardTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IdCardTemplateRequest extends FormRequest
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
            'for.value' => ['required', new Enum(IdCardFor::class)],
            'custom_template_file_name' => 'required|max:255',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('id_card_template');

            $existingTemplate = IdCardTemplate::query()
                ->byTeam()
                ->where('uuid', '!=', $uuid)
                ->where('name', $this->name)
                ->where('for', $this->for)
                ->exists();

            if ($existingTemplate) {
                $validator->errors()->add('name', trans('global.duplicate', ['attribute' => __('academic.id_card.template.template')]));
            }

            $this->merge([
                'for' => $this->for['value'],
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
            'name' => __('academic.id_card.template.props.name'),
            'for' => __('academic.id_card.template.props.for'),
            'custom_template_file_name' => __('academic.id_card.template.props.custom_template_file_name'),
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
            'custom_template_file_name.required_if' => trans('validation.required', ['attribute' => trans('academic.id_card.template.props.custom_template_file_name')]),
        ];
    }
}
