<?php

namespace App\Http\Requests\Employee\Leave;

use App\Concerns\SubordinateAccess;
use App\Helpers\CalHelper;
use App\Models\Employee\Employee;
use App\Models\Employee\Leave\Allocation as LeaveAllocation;
use App\Models\Employee\Leave\Type as LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class AllocationRequest extends FormRequest
{
    use SubordinateAccess;

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
            'employee' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'records' => 'array|required|min:1',
            'records.*.leave_type' => 'required|distinct',
            'records.*.allotted' => 'required|numeric|min:0',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('leave_allocation');

            $employee = Employee::query()
                ->summary()
                ->filterAccessible()
                ->where('employees.uuid', $this->employee)
                ->getOrFail(trans('employee.employee'), 'employee');

            $this->validateEmployeeJoiningDate($employee, $this->start_date, trans('employee.employee'), 'employee');

            $this->validateEmployeeLeavingDate($employee, $this->start_date, trans('employee.employee'), 'employee');

            $overlappingAllocation = LeaveAllocation::query()
                ->whereEmployeeId($employee->id)
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->betweenPeriod($this->start_date, $this->end_date)
                ->count();

            if ($overlappingAllocation) {
                $validator->errors()->add('message', trans('employee.leave.allocation.range_exists', ['start' => CalHelper::showDate($this->start_date), 'end' => CalHelper::showDate($this->end_date)]));
            }

            $leaveTypes = LeaveType::query()
                ->byTeam()
                ->select('id', 'uuid')
                ->get();

            $leaveTypeUuids = $leaveTypes->pluck('uuid')->all();

            $newRecords = [];
            foreach ($this->records as $index => $record) {
                $uuid = Arr::get($record, 'leave_type.uuid');
                if (! in_array($uuid, $leaveTypeUuids)) {
                    $validator->errors()->add('records.'.$index.'.leave_type', trans('validation.exists', ['attribute' => trans('employee.leave.type.type')]));
                } else {
                    $newRecords[] = Arr::add($record, 'leave_type.id', $leaveTypes->firstWhere('uuid', $uuid)->id);
                }
            }

            $this->merge([
                'employee_id' => $employee->id,
                'records' => $newRecords,
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
            'employee' => __('employee.employee'),
            'start_date' => __('employee.leave.allocation.props.start_date'),
            'end_date' => __('employee.leave.allocation.props.end_date'),
            'description' => __('employee.leave.allocation.props.description'),
            'records.*.leave_type' => __('employee.leave.type.type'),
            'records.*.allotted' => __('employee.leave.allocation.props.allotted'),
            'records.*.used' => __('employee.leave.allocation.props.used'),
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
