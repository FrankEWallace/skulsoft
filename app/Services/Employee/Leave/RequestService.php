<?php

namespace App\Services\Employee\Leave;

use App\Enums\Employee\Leave\RequestStatus as LeaveRequestStatus;
use App\Http\Resources\Employee\Leave\TypeResource as LeaveTypeResource;
use App\Models\Employee\Employee;
use App\Models\Employee\Leave\Allocation as LeaveAllocation;
use App\Models\Employee\Leave\Request as LeaveRequest;
use App\Models\Employee\Leave\Type as LeaveType;
use App\Models\Employee\Payroll\Payroll;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RequestService
{
    public function preRequisite(Request $request): array
    {
        $types = LeaveType::query()
            ->byTeam()
            ->get();

        $employee = Employee::query()
            ->auth()
            ->first();

        $leaveAllocation = $employee ? LeaveAllocation::query()
            ->with('records')
            ->whereEmployeeId($employee->id)
            ->where('start_date', '<=', today()->toDateString())
            ->where('end_date', '>=', today()->toDateString())
            ->first() : null;

        $types = $leaveAllocation ? $types->map(function ($type) use ($leaveAllocation) {
            $leaveAllocationRecord = $leaveAllocation->records->where('leave_type_id', $type->id)->first();

            if ($leaveAllocationRecord) {
                $type->balance = $leaveAllocationRecord->allotted - $leaveAllocationRecord->used;
            } else {
                $type->balance = 0;
            }

            return $type;
        }) : $types;

        if ($employee) {
            $request->merge(['has_balance' => true]);
        }

        $types = LeaveTypeResource::collection($types);

        $statuses = LeaveRequestStatus::getOptions();

        return compact('statuses', 'types');
    }

    public function create(Request $request): LeaveRequest
    {
        \DB::beginTransaction();

        $leaveRequest = LeaveRequest::forceCreate($this->formatParams($request));

        $leaveRequest->addMedia($request);

        \DB::commit();

        return $leaveRequest;
    }

    private function formatParams(Request $request, ?LeaveRequest $leaveRequest = null): array
    {
        $formatted = [
            'model_type' => 'Employee',
            'model_id' => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'is_half_day' => $request->boolean('is_half_day'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
        ];

        if (! $leaveRequest) {
            $formatted['status'] = LeaveRequestStatus::REQUESTED;
            $formatted['request_user_id'] = auth()->id();
        }

        return $formatted;
    }

    public function update(Request $request, LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status != LeaveRequestStatus::REQUESTED) {
            throw ValidationException::withMessages(['message' => trans('employee.leave.request.could_not_perform_if_status_updated')]);
        }

        \DB::beginTransaction();

        $leaveRequest->forceFill($this->formatParams($request, $leaveRequest))->save();

        $leaveRequest->updateMedia($request);

        \DB::commit();
    }

    public function deletable(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status != LeaveRequestStatus::REQUESTED) {
            throw ValidationException::withMessages(['message' => trans('employee.leave.request.could_not_perform_if_status_updated')]);
        }

        $payrollGenerated = Payroll::query()
            ->whereEmployeeId($leaveRequest->employee_id)
            ->betweenPeriod($leaveRequest->start_date->value, $leaveRequest->end_date->value)
            ->exists();

        if ($payrollGenerated) {
            throw ValidationException::withMessages(['message' => trans('employee.leave.request.could_not_perform_if_payroll_generated')]);
        }
    }

    public function getLeaveAllocation(LeaveRequest $leaveRequest): ?LeaveAllocation
    {
        return LeaveAllocation::query()
            ->with('records.type')
            ->whereEmployeeId($leaveRequest->model_id)
            ->where('start_date', '<=', $leaveRequest->start_date->value)
            ->where('end_date', '>=', $leaveRequest->end_date->value)
            ->first();
    }
}
