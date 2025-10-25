<?php

namespace App\Http\Controllers\Student\Report;

use App\Http\Controllers\Controller;
use App\Services\Student\Report\BatchWiseAttendanceListService;
use App\Services\Student\Report\BatchWiseAttendanceService;
use Illuminate\Http\Request;

class BatchWiseAttendanceController extends Controller
{
    public function preRequisite(Request $request, BatchWiseAttendanceService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, BatchWiseAttendanceListService $service)
    {
        return $service->filter($request);
    }
}
