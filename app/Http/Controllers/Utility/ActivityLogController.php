<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Services\Utility\ActivityLogListService;
use App\Services\Utility\ActivityLogService;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('feature.available:feature.enable_activity_log');
    }

    public function index(Request $request, ActivityLogListService $service)
    {
        return $service->paginate($request);
    }

    public function destroy(Activity $activityLog, ActivityLogService $service)
    {
        $service->deletable($activityLog);

        $activityLog->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('utility.activity_log.activity_log')]),
        ]);
    }
}
