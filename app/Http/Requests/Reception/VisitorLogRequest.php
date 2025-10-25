<?php

namespace App\Http\Requests\Reception;

use App\Enums\OptionType;
use App\Enums\Reception\VisitorType;
use App\Models\Employee\Employee;
use App\Models\Guardian;
use App\Models\Option;
use App\Models\Reception\VisitorLog;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class VisitorLogRequest extends FormRequest
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
            'type' => ['required', new Enum(VisitorType::class)],
            'count' => 'required|integer|min:1|max:100',
            'purpose' => 'required|uuid',
            'name' => 'required_if:type,other|min:2|max:255',
            'company_name' => 'nullable|min:2|max:255',
            'contact_number' => 'required_if:type,other|max:20',
            'entry_at' => 'required|date_format:Y-m-d H:i:s',
            'exit_at' => 'nullable|date_format:Y-m-d H:i:s|after:entry_time',
            'visitor' => 'nullable|uuid',
            'remarks' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new VisitorLog)->getModelName();

            $visitorLogUuid = $this->route('visitor_log.uuid');

            $purpose = $this->purpose ? Option::query()
                ->byTeam()
                ->whereType(OptionType::VISITING_PURPOSE->value)
                ->whereUuid($this->purpose)
                ->getOrFail(__('reception.visitor_log.purpose.purpose'), 'purpose') : null;

            $visitor = null;
            if ($this->type == 'student') {
                $visitor = Student::query()
                    ->byTeam()
                    ->whereUuid($this->visitor)
                    ->getOrFail(__('student.student'), 'visitor');
            } elseif ($this->type == 'guardian') {
                $visitor = Guardian::query()
                    ->byTeam()
                    ->whereUuid($this->visitor)
                    ->getOrFail(__('guardian.guardian'), 'visitor');
            } elseif ($this->type == 'employee') {
                $visitor = Employee::query()
                    ->byTeam()
                    ->whereUuid($this->visitor)
                    ->getOrFail(__('employee.employee'), 'visitor');
            }

            $employee = $this->employee ? Employee::query()
                ->byTeam()
                ->whereUuid($this->employee)
                ->getOrFail(__('employee.employee'), 'employee') : null;

            $visitorType = $this->type != 'other' ? Str::title($this->type) : null;

            $this->merge([
                'purpose_id' => $purpose?->id,
                'visitor_type' => $visitorType,
                'visitor_id' => $visitor?->id,
                'employee_id' => $employee?->id,
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
            'type' => __('reception.visitor_log.props.type'),
            'count' => __('reception.visitor_log.props.count'),
            'purpose' => __('reception.visitor_log.purpose.purpose'),
            'name' => __('reception.visitor_log.props.name'),
            'company_name' => __('reception.visitor_log.props.company_name'),
            'contact_number' => __('reception.visitor_log.props.contact_number'),
            'entry_at' => __('reception.visitor_log.props.entry_at'),
            'exit_at' => __('reception.visitor_log.props.exit_at'),
            'visitor' => __('reception.visitor_log.visitor'),
            'employee' => __('reception.visitor_log.props.whom_to_meet'),
            'remarks' => __('reception.visitor_log.props.remarks'),
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
            'name.required_if' => __('validation.required', ['attribute' => __('reception.visitor_log.props.name')]),
            'contact_number.required_if' => __('validation.required', ['attribute' => __('reception.visitor_log.props.contact_number')]),
        ];
    }
}
