<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Models\Approval\Request as ApprovalRequest;
use App\Services\Approval\RequestActionService;
use Illuminate\Http\Request;

class RequestActionController extends Controller
{
    public function preRequisite(Request $request, string $approvalRequest, RequestActionService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('action', $approvalRequest);

        return $service->preRequisite($request, $approvalRequest);
    }

    public function cancel(Request $request, string $approvalRequest, RequestActionService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('update', $approvalRequest);

        $service->cancel($request, $approvalRequest);

        return response()->success([
            'message' => trans('global.cancelled', ['attribute' => trans('approval.request.request')]),
        ]);
    }

    public function updateStatus(Request $request, string $approvalRequest, RequestActionService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('action', $approvalRequest);

        $approvalRequest = $service->updateStatus($request, $approvalRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('approval.request.request')]),
        ]);
    }

    public function uploadMedia(Request $request, string $approvalRequest, RequestActionService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('update', $approvalRequest);

        $service->uploadMedia($request, $approvalRequest);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('general.file')]),
        ]);
    }

    public function removeMedia(string $approvalRequest, string $uuid, RequestActionService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('update', $approvalRequest);

        $service->removeMedia($approvalRequest, $uuid);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('general.file')]),
        ]);
    }
}
