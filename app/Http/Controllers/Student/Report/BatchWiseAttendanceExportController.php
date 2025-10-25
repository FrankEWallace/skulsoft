<?php

namespace App\Http\Controllers\Student\Report;

use App\Http\Controllers\Controller;
use App\Services\Student\Report\BatchWiseAttendanceListService;
use Illuminate\Http\Request;

class BatchWiseAttendanceExportController extends Controller
{
    public function __invoke(Request $request, BatchWiseAttendanceListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
