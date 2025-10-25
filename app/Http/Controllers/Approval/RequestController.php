<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\RequestRequest as ApprovalRequestRequest;
use App\Http\Resources\Approval\RequestResource;
use App\Models\Approval\Request as ApprovalRequest;
use App\Models\Employee\Employee;
use App\Services\Approval\RequestListService;
use App\Services\Approval\RequestService;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, RequestService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, RequestListService $service)
    {
        $this->authorize('viewAny', ApprovalRequest::class);

        return $service->paginate($request);
    }

    public function store(ApprovalRequestRequest $request, RequestService $service)
    {
        $this->authorize('create', ApprovalRequest::class);

        $approvalRequest = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('approval.request.request')]),
            'approval_request' => RequestResource::make($approvalRequest),
        ]);
    }

    public function export(Request $request, string $approvalRequest, RequestService $service)
    {
        $approvalRequest = ApprovalRequest::findDetailByUuidOrFail($approvalRequest);

        $this->authorize('view', $approvalRequest);

        abort_if($approvalRequest->status != 'approved', 404);

        $approvalRequest->load(['media.user', 'type.department', 'comments' => function ($q) {
            $q->with('user')->orderBy('created_at', 'desc');
        }, 'activities' => function ($q) {
            $q->with('user')->orderBy('created_at', 'desc');
        }, 'type.levels', 'priority', 'requestRecords']);

        $date = today()->toDateString();

        $employees = Employee::query()
            ->summary($date, true)
            ->where(function ($q) use ($approvalRequest) {
                $q->whereIn('employees.id', $approvalRequest->type->levels->pluck('employee_id')->toArray())
                    ->orWhereIn('user_id', array_merge([$approvalRequest->request_user_id], $approvalRequest->requestRecords->pluck('user_id')->toArray()));
            })
            ->get();

        $request->merge([
            'employees' => $employees,
            'show_details' => true,
        ]);

        $idNumber = $approvalRequest->id;

        $approvalRequest = json_decode(RequestResource::make($approvalRequest)->toJson(), true);

        return view()->first([
            'print.custom.approval.request',
            'print.approval.request',
        ], compact('approvalRequest', 'idNumber'));
    }

    public function show(Request $request, string $approvalRequest, RequestService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('view', $approvalRequest);

        $approvalRequest->load(['media.user', 'type.department', 'comments' => function ($q) {
            $q->with('user')->orderBy('created_at', 'desc');
        }, 'activities' => function ($q) {
            $q->with('user')->orderBy('created_at', 'desc');
        }, 'type.levels', 'priority', 'group', 'nature', 'requestRecords']);

        $date = today()->toDateString();

        $employees = Employee::query()
            ->summary($date, true)
            ->where(function ($q) use ($approvalRequest) {
                $q->whereIn('employees.id', $approvalRequest->type->levels->pluck('employee_id')->toArray())
                    ->orWhereIn('user_id', array_merge([$approvalRequest->request_user_id], $approvalRequest->requestRecords->pluck('user_id')->toArray()));
            })
            ->get();

        $request->merge([
            'employees' => $employees,
            'show_details' => true,
        ]);

        return RequestResource::make($approvalRequest);
    }

    public function update(ApprovalRequestRequest $request, string $approvalRequest, RequestService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('update', $approvalRequest);

        $service->update($request, $approvalRequest);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('approval.request.request')]),
        ]);
    }

    public function destroy(Request $request, string $approvalRequest, RequestService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('delete', $approvalRequest);

        $service->deletable($approvalRequest);

        $service->delete($approvalRequest);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('approval.request.request')]),
        ]);
    }

    public function downloadMedia(string $approvalRequest, string $uuid, RequestService $service)
    {
        $approvalRequest = ApprovalRequest::findByUuidOrFail($approvalRequest);

        $this->authorize('view', $approvalRequest);

        return $approvalRequest->downloadMedia($uuid);
    }
}
