<?php

namespace App\Http\Requests\Recruitment;

use App\Concerns\SimpleValidation;
use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Models\Employee\Designation;
use App\Models\Option;
use App\Models\Recruitment\Application;
use App\Rules\AlphaSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ApplicationRequest extends FormRequest
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
        return [
            'designation' => 'required|uuid',
            'first_name' => ['required', 'min:2', 'max:100', new AlphaSpace],
            'middle_name' => ['nullable', 'max:100', new AlphaSpace],
            'third_name' => ['nullable', 'max:100', new AlphaSpace],
            'last_name' => ['nullable', 'max:100', new AlphaSpace],
            'birth_date' => 'required|date_format:Y-m-d',
            'gender' => ['required', new Enum(Gender::class)],
            'father_name' => ['nullable', 'max:100', new AlphaSpace],
            'mother_name' => ['nullable', 'max:100', new AlphaSpace],
            'contact_number' => 'required|min:4|max:20',
            'email' => 'nullable|email|max:100',
            'application_date' => 'required|date_format:Y-m-d',
            'availability_date' => 'required|date_format:Y-m-d',
            'alternate_records.contact_number' => 'sometimes|min:2|max:100',
            'alternate_records.email' => 'sometimes|nullable|email|min:2|max:100',
            'religion' => 'sometimes|nullable|uuid',
            'category' => 'sometimes|nullable|uuid',
            'caste' => 'sometimes|nullable|uuid',
            'blood_group' => ['sometimes', 'nullable', new Enum(BloodGroup::class)],
            'marital_status' => ['sometimes', 'nullable', new Enum(MaritalStatus::class)],
            'qualification_summary' => 'nullable|min:2|max:100',
            'present_address.address_line1' => 'required|min:2|max:100',
            'present_address.address_line2' => 'nullable|min:2|max:100',
            'present_address.city' => 'required|min:2|max:100',
            'present_address.state' => 'required|min:2|max:100',
            'present_address.zipcode' => 'required|min:2|max:100',
            'present_address.country' => 'required|min:2|max:100',
            'permanent_address.same_as_present_address' => 'sometimes|boolean',
            'permanent_address.address_line1' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.address_line2' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.city' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.state' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.zipcode' => 'sometimes|nullable|min:2|max:100',
            'permanent_address.country' => 'sometimes|nullable|min:2|max:100',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            $validator->after(function ($validator) {
                $this->change($validator, 'alternate_records.email', 'alternate_email');
                $this->change($validator, 'alternate_records.mobile', 'alternate_contact_number');
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
            $mediaModel = (new Application)->getModelName();

            $applicationUuid = $this->route('application');

            $designation = Designation::query()
                ->byTeam()
                ->whereUuid($this->designation)
                ->getOrFail(trans('employee.designation.designation'), 'designation');

            $religion = $this->religion ? Option::query()
                ->whereType(OptionType::RELIGION->value)
                ->whereUuid($this->religion)
                ->first() : null;

            $category = $this->category ? Option::query()
                ->whereType(OptionType::MEMBER_CATEGORY->value)
                ->whereUuid($this->category)
                ->first() : null;

            $caste = $this->caste ? Option::query()
                ->whereType(OptionType::MEMBER_CASTE->value)
                ->whereUuid($this->caste)
                ->first() : null;

            $this->merge([
                'designation_id' => $designation->id,
                'religion_id' => $religion?->id,
                'category_id' => $category?->id,
                'caste_id' => $caste?->id,
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
            'first_name' => __('contact.props.first_name'),
            'last_name' => __('contact.props.last_name'),
            'middle_name' => __('contact.props.middle_name'),
            'third_name' => __('contact.props.third_name'),
            'father_name' => __('contact.props.father_name'),
            'mother_name' => __('contact.props.mother_name'),
            'gender' => __('contact.props.gender'),
            'birth_date' => __('contact.props.birth_date'),
            'religion' => __('contact.religion.religion'),
            'category' => __('contact.category.category'),
            'caste' => __('contact.caste.caste'),
            'blood_group' => __('contact.props.blood_group'),
            'marital_status' => __('contact.props.marital_status'),
            'designation' => __('employee.designation.designation'),
            'contact_number' => __('contact.props.contact_number'),
            'email' => __('contact.props.email'),
            'alternate_records.contact_number' => __('contact.props.alternate_contact_number'),
            'alternate_records.email' => __('contact.props.alternate_email'),
            'present_address.address_line1' => __('contact.props.address.address_line1'),
            'present_address.address_line2' => __('contact.props.address.address_line2'),
            'present_address.city' => __('contact.props.address.city'),
            'present_address.state' => __('contact.props.address.state'),
            'present_address.zipcode' => __('contact.props.address.zipcode'),
            'present_address.country' => __('contact.props.address.country'),
            'permanent_address.same_as_present_address' => __('contact.props.same_as_present_address'),
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
