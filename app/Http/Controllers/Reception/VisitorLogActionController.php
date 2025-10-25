<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Reception\VisitorLog;
use App\Services\Reception\VisitorLogActionService;
use Illuminate\Http\Request;

class VisitorLogActionController extends Controller
{
    public function markExit(Request $request, VisitorLog $visitorLog, VisitorLogActionService $service)
    {
        $this->authorize('create', $visitorLog);

        $service->markExit($request, $visitorLog);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.visitor_log.visitor_log')]),
        ]);
    }
}
