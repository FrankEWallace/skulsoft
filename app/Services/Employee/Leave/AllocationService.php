<?php

namespace App\Services\Employee\Leave;

use App\Concerns\SubordinateAccess;
use App\Enums\Employee\Leave\RequestStatus;
use App\Helpers\CalHelper;
use App\Http\Resources\Employee\Leave\RequestResource as LeaveRequestResource;
use App\Http\Resources\Employee\Leave\TypeResource as LeaveTypeResource;
use App\Models\Employee\Leave\Allocation as LeaveAllocation;
use App\Models\Employee\Leave\AllocationRecord as LeaveAllocationRecord;
use App\Models\Employee\Leave\Request as LeaveRequest;
use App\Models\Employee\Leave\Type as LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AllocationService
{
    use SubordinateAccess;

    public function preRequisite(Request $request): array
    {
        $leaveTypes = LeaveTypeResource::collection(LeaveType::query()
            ->byTeam()
            ->get());

        return compact('leaveTypes');
    }

    public function create(Request $request): LeaveAllocation
    {
        \DB::beginTransaction();

        $leaveAllocation = LeaveAllocation::forceCreate($this->formatParams($request));

        $this->updateRecords($request, $leaveAllocation);

        \DB::commit();

        return $leaveAllocation;
    }

    private function formatParams(Request $request, ?LeaveAllocation $leaveAllocation = null): array
    {
        $formatted = [
            'employee_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ];

        if (! $leaveAllocation) {
            //
        }

        return $formatted;
    }

    private function updateRecords(Request $request, LeaveAllocation $leaveAllocation): void
    {
        $leaveTypeIds = [];

        $pendingLeaveRequests = LeaveRequest::query()
            ->whereModelType('Employee')
            ->whereModelId($leaveAllocation->employee_id)
            ->where('start_date', '>=', $leaveAllocation->start_date->value)
            ->where('end_date', '<=', $leaveAllocation->end_date->value)
            ->where('status', RequestStatus::REQUESTED)
            ->get();

        foreach ($request->records as $index => $record) {
            $leaveAllocationRecord = LeaveAllocationRecord::firstOrCreate([
                'leave_allocation_id' => $leaveAllocation->id,
                'leave_type_id' => Arr::get($record, 'leave_type.id'),
            ]);

            if ($leaveAllocationRecord->used > Arr::get($record, 'allotted', 0)) {
                throw ValidationException::withMessages(['records.'.$index.'.allotted' => trans('employee.leave.allocation.use_count_gt_allocated', ['allotted' => Arr::get($record, 'allotted', 0), 'used' => $leaveAllocationRecord->used])]);
            }

            $pendingLeaveRequest = $pendingLeaveRequests->where('leave_type_id', Arr::get($record, 'leave_type.id'))->first();

            if ($pendingLeaveRequest) {
                $duration = CalHelper::dateDiff($pendingLeaveRequest->start_date->value, $pendingLeaveRequest->end_date->value);

                if ($leaveAllocationRecord->used + $duration > Arr::get($record, 'allotted', 0)) {
                    throw ValidationException::withMessages(['records.'.$index.'.allotted' => trans('employee.leave.allocation.use_count_gt_allocated', ['allotted' => Arr::get($record, 'allotted', 0), 'used' => $leaveAllocationRecord->used])]);
                }
            }

            $leaveAllocationRecord->uuid = $leaveAllocationRecord->uuid ?? (string) Str::uuid();
            $leaveAllocationRecord->allotted = Arr::get($record, 'allotted', 0);
            $leaveAllocationRecord->save();

            $leaveTypeIds[] = Arr::get($record, 'leave_type.id');
        }

        LeaveAllocationRecord::whereLeaveAllocationId($leaveAllocation->id)->where(function ($q) use ($leaveTypeIds) {
            $q->whereNotIn('leave_type_id', $leaveTypeIds)->orWhere('allotted', '=', 0);
        })->delete();
    }

    private function isEditable(Request $request, LeaveAllocation $leaveAllocation): void
    {
        $leaveRequests = LeaveRequest::query()
            ->whereModelType('Employee')
            ->whereModelId($leaveAllocation->employee_id)
            ->where('start_date', '>=', $leaveAllocation->start_date->value)
            ->where('end_date', '<=', $leaveAllocation->end_date->value)
            ->get();

        if (! $leaveRequests->count()) {
            return;
        } else {
            $inputRecords = collect($request->records);
            foreach ($leaveAllocation->records as $index => $leaveAllocationRecord) {
                $inputRecord = $inputRecords->where('leave_type.id', $leaveAllocationRecord->leave_type_id)->first();
                if (! $inputRecord) {
                    throw ValidationException::withMessages(['records.'.$index.'.leave_type' => trans('general.errors.invalid_input')]);
                } else {
                    $pendingLeaveRequests = $leaveRequests->where('leave_type_id', $leaveAllocationRecord->leave_type_id);

                    $pending = 0;
                    foreach ($pendingLeaveRequests as $pendingLeaveRequest) {
                        $pending += CalHelper::dateDiff($pendingLeaveRequest->start_date->value, $pendingLeaveRequest->end_date->value);
                    }

                    if (Arr::get($inputRecord, 'allotted', 0) < $leaveAllocationRecord->used + $pending) {
                        throw ValidationException::withMessages(['records.'.$index.'.allotted' => trans('employee.leave.allocation.use_count_gt_allocated', ['used' => $leaveAllocationRecord->used, 'allotted' => Arr::get($inputRecord, 'allotted', 0)])]);
                    }
                }
            }
        }

        if ($request->employee_id != $leaveAllocation->employee_id) {
            throw ValidationException::withMessages(['employee' => trans('employee.leave.allocation.could_not_perform_if_leave_requested')]);
        }

        $firstLeaveRequestDate = $leaveRequests->sortBy('start_date')->first()->start_date;
        $lastLeaveRequestDate = $leaveRequests->sortByDesc('end_date')->first()->end_date;

        if ($leaveAllocation->start_date->value != $request->start_date && $request->start_date > $firstLeaveRequestDate->value) {
            throw ValidationException::withMessages(['start_date' => trans('employee.leave.allocation.start_date_gt_first_leave_request_date', ['date' => $firstLeaveRequestDate->formatted])]);
        }

        if ($leaveAllocation->end_date->value != $request->end_date && $request->end_date < $lastLeaveRequestDate->value) {
            throw ValidationException::withMessages(['end_date' => trans('employee.leave.allocation.end_date_lt_last_leave_request_date', ['date' => $lastLeaveRequestDate->formatted])]);
        }
    }

    public function update(Request $request, LeaveAllocation $leaveAllocation): void
    {
        $this->isEditable($request, $leaveAllocation);

        \DB::beginTransaction();

        $leaveAllocation->forceFill($this->formatParams($request, $leaveAllocation))->save();

        $this->updateRecords($request, $leaveAllocation);

        \DB::commit();
    }

    public function deletable(LeaveAllocation $leaveAllocation): void
    {
        $usedLeaveAllocation = $leaveAllocation->records()->where('used', '>', 0)->count();

        if ($usedLeaveAllocation) {
            throw ValidationException::withMessages(['message' => trans('employee.leave.allocation.could_not_delete_if_utilized')]);
        }
    }

    public function fetchLeaveRequests(Request $request, LeaveAllocation $leaveAllocation): array
    {
        $leaveRequests = LeaveRequestResource::collection(LeaveRequest::query()
            ->with('type')
            ->whereModelType('Employee')
            ->whereModelId($leaveAllocation->employee_id)
            ->where('start_date', '>=', $leaveAllocation->start_date->value)
            ->where('end_date', '<=', $leaveAllocation->end_date->value)
            ->whereIn('status', [RequestStatus::APPROVED, RequestStatus::PARTIALLY_APPROVED])
            ->get());

        return compact('leaveRequests');
    }
}
