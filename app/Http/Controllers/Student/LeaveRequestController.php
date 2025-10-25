<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\LeaveRequestRequest;
use App\Http\Resources\Student\LeaveRequestResource;
use App\Models\Student\LeaveRequest;
use App\Services\Student\LeaveRequestListService;
use App\Services\Student\LeaveRequestService;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:student:leave-request');
    }

    public function preRequisite(Request $request, LeaveRequestService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, LeaveRequestListService $service)
    {
        return $service->paginate($request);
    }

    public function store(LeaveRequestRequest $request, LeaveRequestService $service)
    {
        $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.leave_request.leave_request')]),
        ]);
    }

    public function show(string $leaveRequest, LeaveRequestService $service)
    {
        $leaveRequest = LeaveRequest::findDetailByUuidOrFail($leaveRequest);

        $leaveRequest->load('media');

        return LeaveRequestResource::make($leaveRequest);
    }

    public function update(LeaveRequestRequest $request, string $leaveRequest, LeaveRequestService $service)
    {
        $leaveRequest = LeaveRequest::findByUuidOrFail($leaveRequest);

        $service->update($request, $leaveRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.leave_request.leave_request')]),
        ]);
    }

    public function destroy(Request $request, string $leaveRequest, LeaveRequestService $service)
    {
        $leaveRequest = LeaveRequest::findByUuidOrFail($leaveRequest);

        $service->deletable($request, $leaveRequest);

        $service->delete($leaveRequest);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.leave_request.leave_request')]),
        ]);
    }

    public function downloadMedia(string $leaveRequest, string $uuid, LeaveRequestService $service)
    {
        $leaveRequest = LeaveRequest::findByUuidOrFail($leaveRequest);

        return $leaveRequest->downloadMedia($uuid);
    }
}
