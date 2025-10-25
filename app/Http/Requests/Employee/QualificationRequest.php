<?php

namespace App\Http\Requests\Employee;

use App\Enums\OptionType;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\Qualification;
use Illuminate\Foundation\Http\FormRequest;

class QualificationRequest extends FormRequest
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
            'level' => 'required',
            'course' => 'required|min:2|max:100',
            'institute' => 'nullable|min:2|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'affiliated_to' => 'nullable|min:2|max:100',
            'result' => 'nullable|min:2|max:200',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $employeeUuid = $this->route('employee');
            $qualificationUuid = $this->route('qualification');

            $employee = Employee::query()
                ->byTeam()
                ->whereUuid($employeeUuid)
                ->firstOrFail();

            $qualificationLevel = Option::query()
                ->byTeam()
                ->whereType(OptionType::QUALIFICATION_LEVEL->value)
                ->whereUuid($this->level)
                ->getOrFail(__('employee.qualification_level.qualification_level'), 'level');

            $existingQualification = Qualification::whereHasMorph(
                'model', [Contact::class],
                function ($q) use ($employee) {
                    $q->whereId($employee->contact_id);
                }
            )
                ->when($qualificationUuid, function ($q, $qualificationUuid) {
                    $q->where('uuid', '!=', $qualificationUuid);
                })
                ->whereCourse($this->course)
                ->exists();

            if ($existingQualification) {
                $validator->errors()->add('course', trans('validation.unique', ['attribute' => __('employee.qualification.props.course')]));
            }

            $this->merge([
                'level_id' => $qualificationLevel->id,
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
            'course' => __('employee.qualification.props.course'),
            'institute' => __('employee.qualification.props.institute'),
            'start_date' => __('employee.qualification.props.start_date'),
            'end_date' => __('employee.qualification.props.end_date'),
            'affiliated_to' => __('employee.qualification.props.affiliated_to'),
            'result' => __('employee.qualification.props.result'),
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
