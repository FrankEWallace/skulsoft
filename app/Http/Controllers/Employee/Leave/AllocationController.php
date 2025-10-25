<?php

namespace App\Http\Controllers\Employee\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Leave\AllocationRequest as LeaveAllocationRequest;
use App\Http\Resources\Employee\Leave\AllocationResource as LeaveAllocationResource;
use App\Models\Employee\Leave\Allocation as LeaveAllocation;
use App\Services\Employee\Leave\AllocationListService as LeaveAllocationListService;
use App\Services\Employee\Leave\AllocationService as LeaveAllocationService;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, LeaveAllocationService $service)
    {
        $this->authorize('preRequisite', LeaveAllocation::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, LeaveAllocationListService $service)
    {
        $this->authorize('viewAny', LeaveAllocation::class);

        return $service->paginate($request);
    }

    public function store(LeaveAllocationRequest $request, LeaveAllocationService $service)
    {
        $this->authorize('create', LeaveAllocation::class);

        $leaveAllocation = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.leave.allocation.allocation')]),
            'leave_allocation' => LeaveAllocationResource::make($leaveAllocation),
        ]);
    }

    public function show(string $leaveAllocation, LeaveAllocationService $service)
    {
        $leaveAllocation = LeaveAllocation::findDetailByUuidOrFail($leaveAllocation);

        $this->authorize('view', $leaveAllocation);

        return LeaveAllocationResource::make($leaveAllocation);
    }

    public function fetchLeaveRequests(Request $request, string $leaveAllocation, LeaveAllocationService $service)
    {
        $leaveAllocation = LeaveAllocation::findDetailByUuidOrFail($leaveAllocation);

        $this->authorize('view', $leaveAllocation);

        return $service->fetchLeaveRequests($request, $leaveAllocation);
    }

    public function update(LeaveAllocationRequest $request, string $leaveAllocation, LeaveAllocationService $service)
    {
        $leaveAllocation = LeaveAllocation::findDetailByUuidOrFail($leaveAllocation);

        $this->authorize('update', $leaveAllocation);

        $service->update($request, $leaveAllocation);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.leave.allocation.allocation')]),
        ]);
    }

    public function destroy(string $leaveAllocation, LeaveAllocationService $service)
    {
        $leaveAllocation = LeaveAllocation::findByUuidOrFail($leaveAllocation);

        $this->authorize('delete', $leaveAllocation);

        $service->deletable($leaveAllocation);

        $leaveAllocation->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.leave.allocation.allocation')]),
        ]);
    }
}
