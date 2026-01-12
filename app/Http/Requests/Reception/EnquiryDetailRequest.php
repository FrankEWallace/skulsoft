<?php

namespace App\Http\Requests\Reception;

use App\Concerns\CustomFormFieldValidation;
use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\FamilyRelation;
use App\Enums\Gender;
use App\Enums\Locality;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Period;
use App\Models\CustomField;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\Reception\Enquiry;
use App\Rules\AlphaSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class EnquiryDetailRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'tab' => ['required', 'in:basic,contact,guardian'],
        ];

        if ($this->tab == 'basic') {
            $rules['period'] = 'required|uuid';
            $rules['type'] = ['nullable', 'uuid'];
            $rules['category'] = 'nullable|uuid';
            $rules['stage'] = 'nullable|uuid';
            $rules['source'] = 'nullable|uuid';
            $rules['employee'] = 'nullable|uuid';
            $rules['date'] = 'required|date_format:Y-m-d';
            $rules['course'] = 'required|uuid';
            $rules['remarks'] = 'nullable|string|max:255';
            $rules['description'] = 'nullable|string|max:1000';
            $rules['first_name'] = ['required', 'min:2', 'max:100', new AlphaSpace];
            $rules['last_name'] = ['max:100', new AlphaSpace];
            $rules['gender'] = ['required', new Enum(Gender::class)];
            $rules['birth_date'] = 'required|date_format:Y-m-d';
            $rules['unique_id_number1'] = 'nullable|string|max:255';
            $rules['unique_id_number2'] = 'nullable|string|max:255';
            $rules['unique_id_number3'] = 'nullable|string|max:255';
            $rules['unique_id_number4'] = 'nullable|string|max:255';
            $rules['unique_id_number5'] = 'nullable|string|max:255';
            $rules['birth_place'] = 'nullable|string|max:255';
            $rules['nationality'] = 'nullable|string|max:255';
            $rules['mother_tongue'] = 'nullable|string|max:255';
            $rules['locality'] = ['nullable', new Enum(Locality::class)];
            $rules['blood_group'] = ['nullable', new Enum(BloodGroup::class)];
            $rules['marital_status'] = ['nullable', new Enum(MaritalStatus::class)];
            $rules['religion'] = 'nullable|uuid';
            $rules['student_caste'] = 'nullable|uuid';
            $rules['student_category'] = 'nullable|uuid';
        } elseif ($this->tab == 'contact') {
            $rules['contact_number'] = 'required|min:2|max:20';
            $rules['email'] = 'nullable|email|min:2|max:50';
            $rules['alternate_records.contact_number'] = 'nullable|min:2|max:100';
            $rules['alternate_records.email'] = 'nullable|email|min:2|max:100';
            $rules['present_address.address_line1'] = 'required|min:2|max:100';
            $rules['present_address.address_line2'] = 'nullable|min:2|max:100';
            $rules['present_address.city'] = 'nullable|min:2|max:100';
            $rules['present_address.state'] = 'nullable|min:2|max:100';
            $rules['present_address.zipcode'] = 'nullable|min:2|max:100';
            $rules['present_address.country'] = 'required|min:2|max:100';
            $rules['permanent_address.same_as_present_address'] = 'boolean';
            $rules['permanent_address.address_line1'] = 'nullable|min:2|max:100';
            $rules['permanent_address.address_line2'] = 'nullable|min:2|max:100';
            $rules['permanent_address.city'] = 'nullable|min:2|max:100';
            $rules['permanent_address.state'] = 'nullable|min:2|max:100';
            $rules['permanent_address.zipcode'] = 'nullable|min:2|max:100';
            $rules['permanent_address.country'] = 'nullable|min:2|max:100';
        } elseif ($this->tab == 'guardian') {
            $rules['guardians'] = 'array';
            $rules['guardians.*.guardian_type'] = ['required', 'in:new,existing'];
            $rules['guardians.*.guardian'] = ['required_if:guardians.*.type,existing'];
            $rules['guardians.*.name'] = ['nullable', 'required_if:guardians.*.guardian_type,new', 'min:2', 'max:100', new AlphaSpace];
            $rules['guardians.*.relation'] = ['required_if:guardians.*.guardian_type,new', new Enum(FamilyRelation::class)];
            $rules['guardians.*.contact_number'] = 'nullable|required_if:guardians.*.guardian_type,new|min:4|max:20';
        }

        if ($this->tab == 'basic' && config('config.contact.enable_middle_name_field')) {
            $rules['middle_name'] = ['nullable', 'max:100', new AlphaSpace];
        }

        if ($this->tab == 'basic' && config('config.contact.enable_third_name_field')) {
            $rules['third_name'] = ['nullable', 'max:100', new AlphaSpace];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Enquiry)->getModelName();

            $enquiryUuid = $this->route('enquiry.uuid');

            if ($this->tab == 'basic') {
                $period = Period::query()
                    ->byTeam()
                    ->whereUuid($this->period)
                    ->getOrFail(__('academic.period.period'), 'period');

                $stage = $this->stage ? Option::query()
                    ->byTeam()
                    ->whereType(OptionType::ENQUIRY_STAGE->value)
                    ->whereUuid($this->stage)
                    ->getOrFail(__('reception.enquiry.stage.stage'), 'stage') : null;

                $type = $this->type ? Option::query()
                    ->byTeam()
                    ->whereType(OptionType::ENQUIRY_TYPE->value)
                    ->whereUuid($this->type)
                    ->getOrFail(__('reception.enquiry.type.type'), 'type') : null;

                $source = $this->source ? Option::query()
                    ->byTeam()
                    ->whereType(OptionType::ENQUIRY_SOURCE->value)
                    ->whereUuid($this->source)
                    ->getOrFail(__('reception.enquiry.source.source'), 'source') : null;

                $employee = $this->employee ? Employee::query()
                    ->byTeam()
                    ->whereUuid($this->employee)
                    ->getOrFail(__('employee.employee'), 'employee') : null;

                $course = Course::query()
                    ->byPeriod($period->id)
                    ->whereUuid($this->course)
                    ->getOrFail(__('academic.course.course'), 'course');

                $religion = $this->religion ? Option::query()
                    ->byTeam()
                    ->whereType(OptionType::RELIGION->value)
                    ->whereUuid($this->religion)
                    ->getOrFail(__('contact.religion.religion'), 'religion') : null;

                $caste = $this->caste ? Option::query()
                    ->byTeam()
                    ->whereType(OptionType::MEMBER_CASTE->value)
                    ->whereUuid($this->caste)
                    ->getOrFail(__('contact.caste.caste'), 'caste') : null;

                $category = $this->category ? Option::query()
                    ->byTeam()
                    ->whereType(OptionType::MEMBER_CATEGORY->value)
                    ->whereUuid($this->category)
                    ->getOrFail(__('contact.category.category'), 'category') : null;

                $customFields = CustomField::query()
                    ->byTeam()
                    ->whereForm(CustomFieldForm::ENQUIRY)
                    ->get();

                $newCustomFields = $this->validateFields($validator, $customFields, $this->input('custom_fields', []));

                $this->merge([
                    'period_id' => $period->id,
                    'stage_id' => $stage?->id,
                    'type_id' => $type?->id,
                    'source_id' => $source?->id,
                    'employee_id' => $employee?->id,
                    'course_id' => $course->id,
                    'religion_id' => $religion?->id,
                    'caste_id' => $caste?->id,
                    'category_id' => $category?->id,
                    'custom_fields' => $newCustomFields,
                ]);
            } elseif ($this->tab == 'contact') {

            } elseif ($this->tab == 'guardian') {

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
            'period' => __('academic.period.period'),
            'stage' => __('reception.enquiry.stage.stage'),
            'course' => __('academic.course.course'),
            'type' => __('reception.enquiry.type.type'),
            'source' => __('reception.enquiry.source.source'),
            'date' => __('reception.enquiry.props.date'),
            'first_name' => __('contact.props.first_name'),
            'middle_name' => __('contact.props.middle_name'),
            'third_name' => __('contact.props.third_name'),
            'last_name' => __('contact.props.last_name'),
            'gender' => __('contact.props.gender'),
            'birth_date' => __('contact.props.birth_date'),
            'unique_id_number1' => config('config.student.unique_id_number1_label'),
            'unique_id_number2' => config('config.student.unique_id_number2_label'),
            'unique_id_number3' => config('config.student.unique_id_number3_label'),
            'unique_id_number4' => config('config.student.unique_id_number4_label'),
            'unique_id_number5' => config('config.student.unique_id_number5_label'),
            'birth_place' => __('contact.props.birth_place'),
            'nationality' => __('contact.props.nationality'),
            'mother_tongue' => __('contact.props.mother_tongue'),
            'blood_group' => __('contact.props.blood_group'),
            'marital_status' => __('contact.props.marital_status'),
            'religion' => __('contact.religion.religion'),
            'student_caste' => __('contact.caste.caste'),
            'student_category' => __('contact.category.category'),
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
            'guardians' => __('guardian.guardian'),
            'guardians.*.guardian' => __('guardian.guardian'),
            'guardians.*.name' => __('guardian.props.name'),
            'guardians.*.relation' => __('contact.props.relation'),
            'guardians.*.contact_number' => __('contact.props.contact_number'),
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
            'guardians.*.contact_number.required_if' => __('validation.required', ['attribute' => __('contact.props.contact_number')]),
            'guardians.*.relation.required_if' => __('validation.required', ['attribute' => __('contact.props.relation')]),
        ];
    }
}
