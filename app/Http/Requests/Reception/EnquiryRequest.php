<?php

namespace App\Http\Requests\Reception;

use App\Concerns\CustomFormFieldValidation;
use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Enums\Reception\EnquiryNature;
use App\Models\Academic\Course;
use App\Models\Academic\Period;
use App\Models\CustomField;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\Reception\Enquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class EnquiryRequest extends FormRequest
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
            'period' => 'required|uuid',
            'nature' => ['required', new Enum(EnquiryNature::class)],
            'type' => 'nullable|uuid',
            'source' => 'nullable|uuid',
            'employee' => 'nullable|uuid',
            'date' => 'required|date_format:Y-m-d',
            'remarks' => 'nullable|string|max:255',
            'name' => 'required|min:2|max:255',
            'contact_number' => 'required|min:2|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
        ];

        if ($this->nature == EnquiryNature::OTHER->value) {
        } elseif ($this->nature == EnquiryNature::ADMISSION->value) {
            $rules['course'] = 'required|uuid';
            $rules['stage'] = 'nullable|uuid';
            $rules['gender'] = ['required', new Enum(Gender::class)];
            $rules['birth_date'] = 'required|date_format:Y-m-d';
            $rules['anniversary_date'] = 'nullable|date_format:Y-m-d';
            $rules['unique_id_number_1'] = 'nullable|string|max:255';
            $rules['unique_id_number_2'] = 'nullable|string|max:255';
            $rules['unique_id_number_3'] = 'nullable|string|max:255';
            $rules['unique_id_number_4'] = 'nullable|string|max:255';
            $rules['unique_id_number_5'] = 'nullable|string|max:255';
            $rules['blood_group'] = ['nullable', new Enum(BloodGroup::class)];
            $rules['marital_status'] = ['nullable', new Enum(MaritalStatus::class)];
            $rules['religion'] = 'nullable|uuid';
            $rules['caste'] = 'nullable|uuid';
            $rules['category'] = 'nullable|uuid';
            $rules['birth_place'] = 'nullable|string|max:255';
            $rules['nationality'] = 'nullable|string|max:255';
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

            $course = $this->course ? Course::query()
                ->byPeriod($period->id)
                ->whereUuid($this->course)
                ->getOrFail(__('academic.course.course'), 'course') : null;

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
                'course_id' => $course?->id,
                'religion_id' => $religion?->id,
                'caste_id' => $caste?->id,
                'category_id' => $category?->id,
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
            'period' => __('academic.period.period'),
            'nature' => __('reception.enquiry.props.nature'),
            'type' => __('reception.enquiry.type.type'),
            'source' => __('reception.enquiry.source.source'),
            'date' => __('reception.enquiry.props.date'),
            'name' => __('reception.enquiry.props.name'),
            'email' => __('reception.enquiry.props.email'),
            'contact_number' => __('reception.enquiry.props.contact_number'),
            'description' => __('reception.enquiry.props.description'),
            'remarks' => __('reception.enquiry.props.remarks'),
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
