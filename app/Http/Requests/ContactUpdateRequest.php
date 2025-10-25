<?php

namespace App\Http\Requests;

use App\Concerns\CustomFormFieldValidation;
use App\Concerns\SimpleValidation;
use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\Gender;
use App\Enums\Locality;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\Guardian;
use App\Models\Option;
use App\Rules\AlphaSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ContactUpdateRequest extends FormRequest
{
    use CustomFormFieldValidation, SimpleValidation;

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
            'first_name' => ['sometimes', 'required', 'min:2', 'max:100', new AlphaSpace],
            'last_name' => ['sometimes', 'max:100', new AlphaSpace],
            'father_name' => ['sometimes', 'nullable', 'min:2', 'max:100', new AlphaSpace],
            'mother_name' => ['sometimes', 'nullable', 'min:2', 'max:100', new AlphaSpace],
            'gender' => ['sometimes', 'required', new Enum(Gender::class)],
            'birth_date' => 'sometimes|required|date_format:Y-m-d|before:today',
            'birth_place' => 'sometimes|max:100',
            'anniversary_date' => 'sometimes|nullable|date_format:Y-m-d|after:birth_date',
            'nationality' => 'sometimes|max:100',
            'religion' => 'sometimes|nullable|uuid',
            'category' => 'sometimes|nullable|uuid',
            'caste' => 'sometimes|nullable|uuid',
            'locality' => ['sometimes', 'nullable', new Enum(Locality::class)],
            'blood_group' => ['sometimes', 'nullable', new Enum(BloodGroup::class)],
            'marital_status' => ['sometimes', 'nullable', new Enum(MaritalStatus::class)],
            'occupation' => 'sometimes|nullable|max:100',
            'annual_income' => 'sometimes|nullable|max:100',
            'organization_name' => 'sometimes|nullable|max:100',
            'designation' => 'sometimes|nullable|max:100',
            'mother_tongue' => 'sometimes|nullable|max:100',
            'unique_id_number1' => 'sometimes|nullable|max:100',
            'unique_id_number2' => 'sometimes|nullable|max:100',
            'unique_id_number3' => 'sometimes|nullable|max:100',
            'unique_id_number4' => 'sometimes|nullable|max:100',
            'unique_id_number5' => 'sometimes|nullable|max:100',
            'contact_number' => 'sometimes|required|min:2|max:20',
            'email' => 'sometimes|nullable|email|min:2|max:50',
            'alternate_records.contact_number' => 'sometimes|min:2|max:100',
            'alternate_records.email' => 'sometimes|nullable|email|min:2|max:100',
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

        $type = 'contact';

        if ($this->route()->getName() == 'students.update') {
            $type = 'student';
        } elseif ($this->route()->getName() == 'employees.update') {
            $type = 'employee';
        } elseif ($this->route()->getName() == 'guardians.update') {
            $type = 'guardian';
        }

        if (config('config.contact.enable_middle_name_field')) {
            $rules['middle_name'] = ['nullable', 'max:100', new AlphaSpace];
        }

        if (config('config.contact.enable_third_name_field')) {
            $rules['third_name'] = ['nullable', 'max:100', new AlphaSpace];
        }

        if (config('config.'.$type.'.is_unique_id_number1_required')) {
            $rules['unique_id_number1'] = 'sometimes|required|max:100';
        }

        if (config('config.'.$type.'.is_unique_id_number2_required')) {
            $rules['unique_id_number2'] = 'sometimes|required|max:100';
        }

        if (config('config.'.$type.'.is_unique_id_number3_required')) {
            $rules['unique_id_number3'] = 'sometimes|required|max:100';
        }

        if (config('config.'.$type.'.is_unique_id_number4_required')) {
            $rules['unique_id_number4'] = 'sometimes|required|max:100';
        }

        if (config('config.'.$type.'.is_unique_id_number5_required')) {
            $rules['unique_id_number5'] = 'sometimes|required|max:100';
        }

        return $rules;
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
            $mergeInputs = [];

            if ($this->route()->getName() == 'students.update') {
                $uuid = Contact::query()
                    ->whereHas('students', function ($q) {
                        $q->whereUuid($this->route('student'));
                    })
                    ->first()?->uuid;
            } elseif ($this->route()->getName() == 'employees.update') {
                $uuid = Contact::query()
                    ->whereHas('employees', function ($q) {
                        $q->whereUuid($this->route('employee'));
                    })
                    ->first()?->uuid;
            } elseif ($this->route()->getName() == 'guardians.update') {
                $uuid = Guardian::query()
                    ->whereUuid($this->route('guardian'))
                    ->first()?->contact?->uuid;
                // Getting issue with collecting guardian contact so commented this
                // $uuid = Contact::query()
                //     ->whereHas('guardians', function ($q) {
                //         $q->whereUuid($this->route('guardian'));
                //     })
                //     ->first()?->uuid;
            } else {
                $uuid = $this->route('contact');
            }

            if ($this->has('first_name') || $this->has('contact_number')) {
                $contact = Contact::query()
                    ->select('first_name', 'middle_name', 'third_name', 'last_name', 'contact_number')
                    ->where('uuid', $uuid)
                    ->first();

                $existingRecord = Contact::query()
                    ->where('uuid', '!=', $uuid)
                    ->when($this->has('first_name'),
                        fn ($query) => $query->where('first_name', $this->input('first_name')), fn ($query) => $query->where('first_name', $contact->first_name)
                    )
                    ->when($this->has('middle_name'),
                        fn ($query) => $query->where('middle_name', $this->input('middle_name')), fn ($query) => $query->where('middle_name', $contact->middle_name)
                    )
                    ->when($this->has('third_name'),
                        fn ($query) => $query->where('third_name', $this->input('third_name')), fn ($query) => $query->where('third_name', $contact->third_name)
                    )
                    ->when($this->has('last_name'),
                        fn ($query) => $query->where('last_name', $this->input('last_name')), fn ($query) => $query->where('last_name', $contact->last_name)
                    )
                    ->when($this->has('contact_number'),
                        fn ($query) => $query->where('contact_number', $this->input('contact_number')), fn ($query) => $query->where('contact_number', $contact->contact_number)
                    )
                    ->exists();

                if ($existingRecord) {
                    $validator->errors()->add('message', __('global.duplicate', ['attribute' => __('contact.contact')]));
                }
            }

            $this->whenHas('religion', function (string $input) use (&$mergeInputs) {
                $religion = Option::query()
                    ->whereType(OptionType::RELIGION->value)
                    ->whereUuid($input)
                    ->first();
                $mergeInputs['religion_id'] = $religion?->id;
            });

            $this->whenHas('category', function (string $input) use (&$mergeInputs) {
                $category = Option::query()
                    ->whereType(OptionType::MEMBER_CATEGORY->value)
                    ->whereUuid($input)
                    ->first();
                $mergeInputs['category_id'] = $category?->id;
            });

            $this->whenHas('caste', function (string $input) use (&$mergeInputs) {
                $caste = Option::query()
                    ->whereType(OptionType::MEMBER_CASTE->value)
                    ->whereUuid($input)
                    ->first();
                $mergeInputs['caste_id'] = $caste?->id;
            });

            $this->whenFilled('unique_id_number1', function (string $input) use ($validator, $uuid) {
                $existingRecord = Contact::query()
                    ->where('uuid', '!=', $uuid)
                    ->whereUniqueIdNumber1($input)
                    ->exists();

                if ($existingRecord) {
                    $validator->errors()->add('unique_id_number1', __('validation.unique', ['attribute' => config('config.contact.unique_id_number1_label')]));
                }
            });

            $this->whenFilled('unique_id_number2', function (string $input) use ($validator, $uuid) {
                $existingRecord = Contact::query()
                    ->where('uuid', '!=', $uuid)
                    ->whereUniqueIdNumber2($input)
                    ->exists();

                if ($existingRecord) {
                    $validator->errors()->add('unique_id_number2', __('validation.unique', ['attribute' => config('config.contact.unique_id_number2_label')]));
                }
            });

            $this->whenFilled('unique_id_number3', function (string $input) use ($validator, $uuid) {
                $existingRecord = Contact::query()
                    ->where('uuid', '!=', $uuid)
                    ->whereUniqueIdNumber3($input)
                    ->exists();

                if ($existingRecord) {
                    $validator->errors()->add('unique_id_number3', __('validation.unique', ['attribute' => config('config.contact.unique_id_number3_label')]));
                }
            });

            $this->whenFilled('unique_id_number4', function (string $input) use ($validator, $uuid) {
                $existingRecord = Contact::query()
                    ->where('uuid', '!=', $uuid)
                    ->whereUniqueIdNumber4($input)
                    ->exists();

                if ($existingRecord) {
                    $validator->errors()->add('unique_id_number4', __('validation.unique', ['attribute' => config('config.contact.unique_id_number4_label')]));
                }
            });

            $this->whenFilled('unique_id_number5', function (string $input) use ($validator, $uuid) {
                $existingRecord = Contact::query()
                    ->where('uuid', '!=', $uuid)
                    ->whereUniqueIdNumber5($input)
                    ->exists();

                if ($existingRecord) {
                    $validator->errors()->add('unique_id_number5', __('validation.unique', ['attribute' => config('config.contact.unique_id_number5_label')]));
                }
            });

            $newCustomFields = [];
            if ($this->route()->getName() == 'students.update' && $this->has('custom_fields')) {
                $customFields = CustomField::query()
                    ->byTeam()
                    ->whereForm(CustomFieldForm::STUDENT)
                    ->get();

                $newCustomFields = $this->validateFields($validator, $customFields, $this->input('custom_fields', []));
            } elseif ($this->route()->getName() == 'employees.update' && $this->has('custom_fields')) {
                $customFields = CustomField::query()
                    ->byTeam()
                    ->whereForm(CustomFieldForm::EMPLOYEE)
                    ->get();

                $newCustomFields = $this->validateFields($validator, $customFields, $this->input('custom_fields', []));
            }

            $this->merge([
                'remove_inputs' => ['religion', 'category', 'caste', 'present_address', 'permanent_address'],
                'merge_inputs' => $mergeInputs,
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
        $type = 'contact';

        if ($this->route()->getName() == 'students.update') {
            $type = 'student';
        } elseif ($this->route()->getName() == 'employees.update') {
            $type = 'employee';
        } elseif ($this->route()->getName() == 'guardians.update') {
            $type = 'guardian';
        }

        return [
            'first_name' => __('contact.props.first_name'),
            'last_name' => __('contact.props.last_name'),
            'middle_name' => __('contact.props.middle_name'),
            'third_name' => __('contact.props.third_name'),
            'father_name' => __('contact.props.father_name'),
            'mother_name' => __('contact.props.mother_name'),
            'gender' => __('contact.props.gender'),
            'birth_date' => __('contact.props.birth_date'),
            'birth_place' => __('contact.props.birth_place'),
            'anniversary_date' => __('contact.props.anniversary_date'),
            'nationality' => __('contact.props.nationality'),
            'religion' => __('contact.religion.religion'),
            'category' => __('contact.category.category'),
            'caste' => __('contact.caste.caste'),
            'blood_group' => __('contact.props.blood_group'),
            'marital_status' => __('contact.props.marital_status'),
            'mother_tongue' => __('contact.props.mother_tongue'),
            'unique_id_number1' => config('config.'.$type.'.unique_id_number1_label'),
            'unique_id_number2' => config('config.'.$type.'.unique_id_number2_label'),
            'unique_id_number3' => config('config.'.$type.'.unique_id_number3_label'),
            'unique_id_number4' => config('config.'.$type.'.unique_id_number4_label'),
            'unique_id_number5' => config('config.'.$type.'.unique_id_number5_label'),
            'occupation' => __('contact.props.occupation'),
            'annual_income' => __('contact.props.annual_income'),
            'organization_name' => __('contact.props.organization_name'),
            'designation' => __('contact.props.designation'),
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
