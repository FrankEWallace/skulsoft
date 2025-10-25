<?php

namespace App\Http\Requests\Utility;

use App\Concerns\CustomFormFieldValidation;
use App\Enums\CustomFieldForm;
use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;

class TodoRequest extends FormRequest
{
    use CustomFormFieldValidation;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $customFields = CustomField::query()
                ->byTeam()
                ->whereForm(CustomFieldForm::TODO)
                ->get();

            $newCustomFields = $this->validateFields($validator, $customFields, $this->input('custom_fields', []));

            $this->merge([
                'custom_fields' => $newCustomFields,
            ]);
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:2|max:500',
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i:s',
        ];
    }
}
