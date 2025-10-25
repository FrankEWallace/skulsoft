<?php

namespace App\Http\Controllers\Employee\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Employee\Attendance\TimesheetListService;
use Illuminate\Http\Request;

class TimesheetExportController extends Controller
{
    public function __invoke(Request $request, TimesheetListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
