<?php

namespace App\Services\Employee\Leave;

use App\Models\Employee\Leave\AllocationRecord as LeaveAllocationRecord;
use App\Models\Employee\Leave\Request as LeaveRequest;
use App\Models\Employee\Leave\Type as LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TypeService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function create(Request $request): LeaveType
    {
        \DB::beginTransaction();

        $leaveType = LeaveType::forceCreate($this->formatParams($request));

        \DB::commit();

        return $leaveType;
    }

    private function formatParams(Request $request, ?LeaveType $leaveType = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'alias' => $request->alias,
            'description' => $request->description,
        ];

        if (! $leaveType) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, LeaveType $leaveType): void
    {
        \DB::beginTransaction();

        $leaveType->forceFill($this->formatParams($request, $leaveType))->save();

        \DB::commit();
    }

    public function deletable(LeaveType $leaveType): void
    {
        $leaveAllocationExists = LeaveAllocationRecord::whereLeaveTypeId($leaveType->id)->exists();

        if ($leaveAllocationExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.leave.type.type'), 'dependency' => trans('employee.leave.allocation.allocation')])]);
        }

        $leaveRequestExists = LeaveRequest::whereLeaveTypeId($leaveType->id)->exists();

        if ($leaveRequestExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.leave.type.type'), 'dependency' => trans('employee.leave.request.request')])]);
        }
    }
}
