<?php

namespace App\Http\Controllers\Employee\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Employee\Attendance\WorkShiftListService;
use Illuminate\Http\Request;

class WorkShiftExportController extends Controller
{
    public function __invoke(Request $request, WorkShiftListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
