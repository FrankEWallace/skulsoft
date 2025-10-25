<?php

namespace App\Http\Controllers\Student\Report;

use App\Http\Controllers\Controller;
use App\Services\Student\Report\DateWiseAttendanceListService;
use App\Services\Student\Report\DateWiseAttendanceService;
use Illuminate\Http\Request;

class DateWiseAttendanceController extends Controller
{
    public function preRequisite(Request $request, DateWiseAttendanceService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, DateWiseAttendanceListService $service)
    {
        return $service->filter($request);
    }
}
