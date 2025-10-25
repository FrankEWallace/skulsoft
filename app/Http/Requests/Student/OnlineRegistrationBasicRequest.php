<?php

namespace App\Http\Requests\Student;

use App\Concerns\CustomFormFieldValidation;
use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\CustomField;
use App\Models\Student\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OnlineRegistrationBasicRequest extends FormRequest
{
    use CustomFormFieldValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'gender' => ['required', new Enum(Gender::class)],
            'birth_date' => 'required|date_format:Y-m-d',
            'anniversary_date' => 'nullable|date_format:Y-m-d',
            'birth_place' => 'nullable|string|max:255',
            'nationality' => 'required|string|max:255',
            'mother_tongue' => 'required|string|max:255',
            'blood_group' => ['required', new Enum(BloodGroup::class)],
            'marital_status' => ['nullable', new Enum(MaritalStatus::class)],
            'category' => 'nullable|uuid',
            'caste' => 'nullable|uuid',
            'religion' => 'nullable|uuid',
        ];

        if (config('config.student.is_unique_id_number1_required')) {
            $rules['unique_id_number1'] = 'sometimes|required|max:100';
        }

        if (config('config.student.is_unique_id_number2_required')) {
            $rules['unique_id_number2'] = 'sometimes|required|max:100';
        }

        if (config('config.student.is_unique_id_number3_required')) {
            $rules['unique_id_number3'] = 'sometimes|required|max:100';
        }

        if (config('config.student.is_unique_id_number4_required')) {
            $rules['unique_id_number4'] = 'sometimes|required|max:100';
        }

        if (config('config.student.is_unique_id_number5_required')) {
            $rules['unique_id_number5'] = 'sometimes|required|max:100';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $authToken = $this->header('auth-token');

            $registration = Registration::query()
                ->with('period')
                ->where('meta->application_number', $this->route('number'))
                ->where('meta->auth_token', $authToken)
                ->firstOrFail();

            $customFields = CustomField::query()
                ->byTeam($registration->period->team_id)
                ->whereForm(CustomFieldForm::REGISTRATION)
                ->get();

            $newCustomFields = $this->validateFields($validator, $customFields, $this->input('custom_fields'));

            $this->merge([
                'custom_fields' => $newCustomFields,
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
            'father_name' => __('contact.props.father_name'),
            'mother_name' => __('contact.props.mother_name'),
            'gender' => __('contact.props.gender'),
            'birth_date' => __('contact.props.birth_date'),
            'anniversary_date' => __('contact.props.anniversary_date'),
            'birth_place' => __('contact.props.birth_place'),
            'nationality' => __('contact.props.nationality'),
            'mother_tongue' => __('contact.props.mother_tongue'),
            'blood_group' => __('contact.props.blood_group'),
            'marital_status' => __('contact.props.marital_status'),
            'category' => __('contact.category.category'),
            'caste' => __('contact.caste.caste'),
            'religion' => __('contact.religion.religion'),
            'unique_id_number1' => config('config.student.unique_id_number1_label'),
            'unique_id_number2' => config('config.student.unique_id_number2_label'),
            'unique_id_number3' => config('config.student.unique_id_number3_label'),
            'unique_id_number4' => config('config.student.unique_id_number4_label'),
            'unique_id_number5' => config('config.student.unique_id_number5_label'),
        ];
    }
}
