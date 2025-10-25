<?php

namespace App\Http\Controllers\Student\Report;

use App\Http\Controllers\Controller;
use App\Services\Student\Report\SubjectWiseAttendanceListService;
use App\Services\Student\Report\SubjectWiseAttendanceService;
use Illuminate\Http\Request;

class SubjectWiseAttendanceController extends Controller
{
    public function preRequisite(Request $request, SubjectWiseAttendanceService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, SubjectWiseAttendanceListService $service)
    {
        return $service->filter($request);
    }
}
