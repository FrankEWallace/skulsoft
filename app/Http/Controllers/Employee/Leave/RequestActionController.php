<?php

namespace App\Http\Controllers\Employee\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Leave\RequestStatusRequest as LeaveRequestStatusRequest;
use App\Models\Employee\Leave\Request as LeaveRequest;
use App\Services\Employee\Leave\RequestActionService as LeaveRequestActionService;
use Illuminate\Http\Request;

class RequestActionController extends Controller
{
    public function undoStatus(Request $request, string $leaveRequest, LeaveRequestActionService $service)
    {
        $leaveRequest = LeaveRequest::findByUuidOrFail($leaveRequest);

        $this->authorize('action', $leaveRequest);

        $service->undoStatus($request, $leaveRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.leave.request.props.status')]),
        ]);
    }

    public function updateStatus(LeaveRequestStatusRequest $request, string $leaveRequest, LeaveRequestActionService $service)
    {
        $leaveRequest = $request->leave_request;

        $this->authorize('action', $leaveRequest);

        $service->updateStatus($request, $leaveRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.leave.request.props.status')]),
        ]);
    }
}
