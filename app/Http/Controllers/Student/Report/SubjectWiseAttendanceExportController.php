<?php

namespace App\Http\Controllers\Student\Report;

use App\Http\Controllers\Controller;
use App\Services\Student\Report\SubjectWiseAttendanceListService;
use Illuminate\Http\Request;

class SubjectWiseAttendanceExportController extends Controller
{
    public function __invoke(Request $request, SubjectWiseAttendanceListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
