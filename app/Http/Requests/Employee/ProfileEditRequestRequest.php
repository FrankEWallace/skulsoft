<?php

namespace App\Http\Requests\Employee;

use App\Concerns\SimpleValidation;
use App\Enums\BloodGroup;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Models\Option;
use App\Rules\ContactNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProfileEditRequestRequest extends FormRequest
{
    use SimpleValidation;

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
        $rules = [
            'contact_number' => ['required', new ContactNumber],
            'alternate_contact_number' => ['nullable', new ContactNumber],
            'email' => ['required', 'email'],
            'father_name' => 'nullable|min:2|max:100',
            'mother_name' => 'nullable|min:2|max:100',
            'alternate_email' => ['nullable', 'email'],
            'birth_place' => ['required', 'string', 'max:50'],
            'nationality' => ['required', 'string', 'max:50'],
            'mother_tongue' => ['required', 'string', 'max:50'],
            'blood_group' => ['nullable', new Enum(BloodGroup::class)],
            'marital_status' => ['nullable', new Enum(MaritalStatus::class)],
            'category' => ['nullable', 'uuid'],
            'religion' => ['required', 'uuid'],
            'caste' => ['nullable', 'uuid'],
            'present_address.address_line1' => 'sometimes|required|min:2|max:100',
            'present_address.address_line2' => 'sometimes|nullable|min:2|max:100',
            'present_address.city' => 'sometimes|nullable|min:2|max:100',
            'present_address.state' => 'sometimes|nullable|min:2|max:100',
            'present_address.zipcode' => 'sometimes|nullable|min:2|max:100',
            'present_address.country' => 'sometimes|required|min:2|max:100',
            'permanent_address.same_as_present_address' => 'sometimes|boolean',
            'permanent_address.address_line1' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.address_line2' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.city' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.state' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.zipcode' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.country' => 'sometimes|nullable|min:2|max:100',
        ];

        if (config('config.contact.is_unique_id_number1_required')) {
            $rules['unique_id_number1'] = 'sometimes|required|max:100';
        }

        if (config('config.contact.is_unique_id_number2_required')) {
            $rules['unique_id_number2'] = 'sometimes|required|max:100';
        }

        if (config('config.contact.is_unique_id_number3_required')) {
            $rules['unique_id_number3'] = 'sometimes|required|max:100';
        }

        if (config('config.contact.is_unique_id_number4_required')) {
            $rules['unique_id_number4'] = 'sometimes|required|max:100';
        }

        if (config('config.contact.is_unique_id_number5_required')) {
            $rules['unique_id_number5'] = 'sometimes|required|max:100';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            $validator->after(function ($validator) {
                $this->change($validator, 'present_address.address_line1', 'present_address_address_line1');
                $this->change($validator, 'present_address.address_line2', 'present_address_address_line2');
                $this->change($validator, 'present_address.city', 'present_address_city');
                $this->change($validator, 'present_address.state', 'present_address_state');
                $this->change($validator, 'present_address.zipcode', 'present_address_zipcode');
                $this->change($validator, 'present_address.country', 'present_address_country');
                $this->change($validator, 'permanent_address.address_line1', 'permanent_address_address_line1');
                $this->change($validator, 'permanent_address.address_line2', 'permanent_address_address_line2');
                $this->change($validator, 'permanent_address.city', 'permanent_address_city');
                $this->change($validator, 'permanent_address.state', 'permanent_address_state');
                $this->change($validator, 'permanent_address.zipcode', 'permanent_address_zipcode');
                $this->change($validator, 'permanent_address.country', 'permanent_address_country');
            });

            return;
        }

        $validator->after(function ($validator) {
            $mergeInputs = [];

            $this->whenFilled('religion', function (string $input) use (&$mergeInputs) {
                $religion = Option::query()
                    ->whereType(OptionType::RELIGION->value)
                    ->whereUuid($input)
                    ->first();
                $mergeInputs['religion'] = $religion?->name;
            });

            $this->whenFilled('category', function (string $input) use (&$mergeInputs) {
                $category = Option::query()
                    ->whereType(OptionType::MEMBER_CATEGORY->value)
                    ->whereUuid($input)
                    ->first();
                $mergeInputs['category'] = $category?->name;
            });

            $this->whenFilled('caste', function (string $input) use (&$mergeInputs) {
                $caste = Option::query()
                    ->whereType(OptionType::MEMBER_CASTE->value)
                    ->whereUuid($input)
                    ->first();
                $mergeInputs['caste'] = $caste?->name;
            });

            $this->merge([
                ...$mergeInputs,
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
            'birth_place' => __('contact.props.birth_place'),
            'anniversary_date' => __('contact.props.anniversary_date'),
            'father_name' => __('contact.props.father_name'),
            'mother_name' => __('contact.props.mother_name'),
            'nationality' => __('contact.props.nationality'),
            'religion' => __('contact.religion.religion'),
            'category' => __('contact.category.category'),
            'caste' => __('contact.caste.caste'),
            'blood_group' => __('contact.props.blood_group'),
            'marital_status' => __('contact.props.marital_status'),
            'mother_tongue' => __('contact.props.mother_tongue'),
            'unique_id_number1' => config('config.contact.unique_id_number1_label'),
            'unique_id_number2' => config('config.contact.unique_id_number2_label'),
            'unique_id_number3' => config('config.contact.unique_id_number3_label'),
            'unique_id_number4' => config('config.contact.unique_id_number4_label'),
            'unique_id_number5' => config('config.contact.unique_id_number5_label'),
            'contact_number' => __('contact.props.contact_number'),
            'email' => __('contact.props.email'),
            'alternate_contact_number' => __('contact.props.alternate_contact_number'),
            'alternate_email' => __('contact.props.alternate_email'),
            'present_address.address_line1' => __('contact.props.address.address_line1'),
            'present_address.address_line2' => __('contact.props.address.address_line2'),
            'present_address.city' => __('contact.props.address.city'),
            'present_address.state' => __('contact.props.address.state'),
            'present_address.zipcode' => __('contact.props.address.zipcode'),
            'present_address.country' => __('contact.props.address.country'),
            'permanent_address.same_as_present_address' => __('contact.props.address.same_as_present_address'),
            'permanent_address.address_line1' => __('contact.props.address.address_line1'),
            'permanent_address.address_line2' => __('contact.props.address.address_line2'),
            'permanent_address.city' => __('contact.props.address.city'),
            'permanent_address.state' => __('contact.props.address.state'),
            'permanent_address.zipcode' => __('contact.props.address.zipcode'),
            'permanent_address.country' => __('contact.props.address.country'),
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
