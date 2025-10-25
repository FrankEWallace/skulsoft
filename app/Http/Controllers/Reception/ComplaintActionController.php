<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\ComplaintAssignRequest;
use App\Http\Requests\Reception\ComplaintLogRequest;
use App\Models\Reception\Complaint;
use App\Services\Reception\ComplaintActionService;

class ComplaintActionController extends Controller
{
    public function assign(ComplaintAssignRequest $request, string $complaint, ComplaintActionService $service)
    {
        $complaint = Complaint::findByUuidOrFail($complaint);

        $this->authorize('update', $complaint);

        $service->assign($request, $complaint);

        return response()->success([
            'message' => trans('global.assigned', ['attribute' => trans('employee.employee')]),
        ]);
    }

    public function unassign(string $complaint, string $employee, ComplaintActionService $service)
    {
        $complaint = Complaint::findByUuidOrFail($complaint);

        $this->authorize('update', $complaint);

        $service->unassign($complaint, $employee);

        return response()->success([
            'message' => trans('global.unassigned', ['attribute' => trans('employee.employee')]),
        ]);
    }

    public function addLog(ComplaintLogRequest $request, string $complaint, ComplaintActionService $service)
    {
        $complaint = Complaint::findByUuidOrFail($complaint);

        $this->authorize('action', $complaint);

        $service->addLog($request, $complaint);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.complaint.props.status')]),
        ]);
    }

    public function removeLog(string $complaint, string $log, ComplaintActionService $service)
    {
        $complaint = Complaint::findByUuidOrFail($complaint);

        $this->authorize('action', $complaint);

        $service->removeLog($complaint, $log);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.complaint.props.status')]),
        ]);
    }
}
