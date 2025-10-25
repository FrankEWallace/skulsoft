<?php

namespace App\Http\Requests\Employee;

use App\Enums\OptionType;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Experience;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class ExperienceRequest extends FormRequest
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
            'headline' => 'required|min:2|max:200',
            'title' => 'required|min:2|max:100',
            'organization_name' => 'required|min:2|max:100',
            'location' => 'required|min:2|max:100',
            'employment_type' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'job_profile' => 'nullable|min:2|max:10000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $employeeUuid = $this->route('employee');
            $experienceUuid = $this->route('experience');

            $employee = Employee::query()
                ->byTeam()
                ->whereUuid($employeeUuid)
                ->firstOrFail();

            $employmentType = Option::query()
                ->byTeam()
                ->whereType(OptionType::EMPLOYMENT_TYPE->value)
                ->whereUuid($this->employment_type)
                ->getOrFail(__('employee.employment_type.employment_type'), 'employment_type');

            $existingExperience = Experience::whereHasMorph(
                'model', [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
                ->when($experienceUuid, function ($q, $experienceUuid) {
                    $q->where('uuid', '!=', $experienceUuid);
                })
                ->whereOrganizationName($this->organization_name)
                ->exists();

            if ($existingExperience) {
                $validator->errors()->add('organization_name', trans('validation.unique', ['attribute' => __('employee.experience.props.organization_name')]));
            }

            $this->merge([
                'employment_type_id' => $employmentType->id,
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
            'headline' => __('employee.experience.props.headline'),
            'title' => __('employee.experience.props.title'),
            'organization_name' => __('employee.experience.props.organization_name'),
            'location' => __('employee.experience.props.location'),
            'employment_type' => __('employee.employment_type.employment_type'),
            'start_date' => __('employee.experience.props.start_date'),
            'end_date' => __('employee.experience.props.end_date'),
            'job_profile' => __('employee.experience.props.job_profile'),
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
