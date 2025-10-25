<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\CallLogRequest;
use App\Http\Resources\Reception\CallLogResource;
use App\Models\Reception\CallLog;
use App\Services\Reception\CallLogListService;
use App\Services\Reception\CallLogService;
use Illuminate\Http\Request;

class CallLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, CallLogService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, CallLogListService $service)
    {
        $this->authorize('viewAny', CallLog::class);

        return $service->paginate($request);
    }

    public function store(CallLogRequest $request, CallLogService $service)
    {
        $this->authorize('create', CallLog::class);

        $callLog = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('reception.call_log.call_log')]),
            'call_log' => CallLogResource::make($callLog),
        ]);
    }

    public function show(CallLog $callLog, CallLogService $service)
    {
        $this->authorize('view', $callLog);

        $callLog->load('purpose', 'media');

        return CallLogResource::make($callLog);
    }

    public function update(CallLogRequest $request, CallLog $callLog, CallLogService $service)
    {
        $this->authorize('update', $callLog);

        $service->update($request, $callLog);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.call_log.call_log')]),
        ]);
    }

    public function destroy(CallLog $callLog, CallLogService $service)
    {
        $this->authorize('delete', $callLog);

        $service->deletable($callLog);

        $callLog->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('reception.call_log.call_log')]),
        ]);
    }

    public function downloadMedia(CallLog $callLog, string $uuid, CallLogService $service)
    {
        $this->authorize('view', $callLog);

        return $callLog->downloadMedia($uuid);
    }
}
