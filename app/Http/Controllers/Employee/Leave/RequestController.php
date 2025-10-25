<?php

namespace App\Http\Controllers\Employee\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Leave\RequestRequest as LeaveRequestRequest;
use App\Http\Resources\Employee\Leave\RequestResource as LeaveRequestResource;
use App\Models\Employee\Leave\Request as LeaveRequest;
use App\Services\Employee\Leave\RequestListService as LeaveRequestListService;
use App\Services\Employee\Leave\RequestService as LeaveRequestService;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, LeaveRequestService $service)
    {
        $this->authorize('preRequisite', LeaveRequest::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, LeaveRequestListService $service)
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return $service->paginate($request);
    }

    public function store(LeaveRequestRequest $request, LeaveRequestService $service)
    {
        $this->authorize('create', LeaveRequest::class);

        $leaveRequest = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.leave.request.request')]),
            'leave_request' => LeaveRequestResource::make($leaveRequest),
        ]);
    }

    public function show(Request $request, string $leaveRequest, LeaveRequestService $service)
    {
        $leaveRequest = LeaveRequest::findDetailByUuidOrFail($leaveRequest);

        $this->authorize('view', $leaveRequest);

        $leaveRequest->load('media');

        $leaveAllocation = $service->getLeaveAllocation($leaveRequest);

        $leaveRequest->allocation = $leaveAllocation;

        $request->merge(['has_leave_allocation' => true]);

        return LeaveRequestResource::make($leaveRequest);
    }

    public function update(LeaveRequestRequest $request, string $leaveRequest, LeaveRequestService $service)
    {
        $leaveRequest = LeaveRequest::findDetailByUuidOrFail($leaveRequest);

        $this->authorize('update', $leaveRequest);

        $service->update($request, $leaveRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.leave.request.request')]),
        ]);
    }

    public function destroy(string $leaveRequest, LeaveRequestService $service)
    {
        $leaveRequest = LeaveRequest::findByUuidOrFail($leaveRequest);

        $this->authorize('delete', $leaveRequest);

        $service->deletable($leaveRequest);

        $leaveRequest->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.leave.request.request')]),
        ]);
    }

    public function downloadMedia(string $leaveRequest, string $uuid, LeaveRequestService $service)
    {
        $leaveRequest = LeaveRequest::findByUuidOrFail($leaveRequest);

        $this->authorize('view', $leaveRequest);

        return $leaveRequest->downloadMedia($uuid);
    }
}
