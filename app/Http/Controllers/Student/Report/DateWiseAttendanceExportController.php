<?php

namespace App\Http\Controllers\Student\Report;

use App\Http\Controllers\Controller;
use App\Services\Student\Report\DateWiseAttendanceListService;
use Illuminate\Http\Request;

class DateWiseAttendanceExportController extends Controller
{
    public function __invoke(Request $request, DateWiseAttendanceListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
