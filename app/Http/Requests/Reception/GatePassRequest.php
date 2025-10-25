<?php

namespace App\Http\Requests\Reception;

use App\Enums\OptionType;
use App\Enums\Reception\GatePassTo;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\Reception\GatePass;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GatePassRequest extends FormRequest
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
            'to' => ['required', new Enum(GatePassTo::class)],
            'purpose' => 'required|uuid',
            'start_at' => 'required|date_format:Y-m-d H:i:s',
            'requesters' => 'required|array|min:1',
            'reason' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new GatePass)->getModelName();

            $gatePassUuid = $this->route('gate_pass.uuid');

            $purpose = $this->purpose ? Option::query()
                ->byTeam()
                ->whereType(OptionType::GATE_PASS_PURPOSE->value)
                ->whereUuid($this->purpose)
                ->getOrFail(__('reception.gate_pass.purpose.purpose'), 'purpose') : null;

            $studentAudiences = [];
            $employeeAudiences = [];
            if ($this->to == 'student') {
                $requesters = Student::query()
                    ->byTeam()
                    ->whereIn('uuid', $this->requesters)
                    ->listOrFail(__('student.student'), 'requester');
                $studentAudiences = $requesters->pluck('id')->toArray();
            } elseif ($this->to == 'employee') {
                $requesters = Employee::query()
                    ->byTeam()
                    ->whereIn('uuid', $this->requesters)
                    ->listOrFail(__('employee.employee'), 'requester');
                $employeeAudiences = $requesters->pluck('id')->toArray();
            }

            $this->merge([
                'purpose_id' => $purpose?->id,
                'requester_type' => $this->to,
                'student_audience_type' => 'student_wise',
                'employee_audience_type' => 'employee_wise',
                'student_audiences' => $studentAudiences,
                'employee_audiences' => $employeeAudiences,
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
            'to' => __('reception.gate_pass.props.to'),
            'requester' => __('reception.gate_pass.props.requester'),
            'purpose' => __('reception.gate_pass.props.purpose'),
            'start_at' => __('reception.gate_pass.props.datetime'),
            'reason' => __('reception.gate_pass.props.reason'),
            'remarks' => __('reception.gate_pass.props.remarks'),
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
