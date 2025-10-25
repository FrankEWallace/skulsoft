<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\VisitorLogRequest;
use App\Http\Resources\Reception\VisitorLogResource;
use App\Models\Reception\VisitorLog;
use App\Services\Reception\VisitorLogListService;
use App\Services\Reception\VisitorLogService;
use Illuminate\Http\Request;

class VisitorLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, VisitorLogService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, VisitorLogListService $service)
    {
        $this->authorize('viewAny', VisitorLog::class);

        return $service->paginate($request);
    }

    public function store(VisitorLogRequest $request, VisitorLogService $service)
    {
        $this->authorize('create', VisitorLog::class);

        $visitorLog = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('reception.visitor_log.visitor_log')]),
            'visitor_log' => VisitorLogResource::make($visitorLog),
        ]);
    }

    public function show(VisitorLog $visitorLog, VisitorLogService $service)
    {
        $this->authorize('view', $visitorLog);

        $visitorLog->load(['purpose', 'employee' => fn ($q) => $q->summary(), 'visitor.contact', 'media']);

        return VisitorLogResource::make($visitorLog);
    }

    public function update(VisitorLogRequest $request, VisitorLog $visitorLog, VisitorLogService $service)
    {
        $this->authorize('update', $visitorLog);

        $service->update($request, $visitorLog);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.visitor_log.visitor_log')]),
        ]);
    }

    public function destroy(VisitorLog $visitorLog, VisitorLogService $service)
    {
        $this->authorize('delete', $visitorLog);

        $service->deletable($visitorLog);

        $visitorLog->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('reception.visitor_log.visitor_log')]),
        ]);
    }

    public function downloadMedia(VisitorLog $visitorLog, string $uuid, VisitorLogService $service)
    {
        $this->authorize('view', $visitorLog);

        return $visitorLog->downloadMedia($uuid);
    }

    public function export(VisitorLog $visitorLog, VisitorLogService $service)
    {
        $this->authorize('view', $visitorLog);

        $visitorLog->load(['purpose', 'employee' => fn ($q) => $q->summary(), 'visitor.contact', 'media']);

        $visitorLog = json_decode(VisitorLogResource::make($visitorLog)->toJson(), true);

        return view()->first([config('config.print.custom_path').'reception.visitor-pass', 'print.reception.visitor-pass'], compact('visitorLog'));
    }
}
