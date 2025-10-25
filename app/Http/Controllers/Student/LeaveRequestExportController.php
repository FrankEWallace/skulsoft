<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\LeaveRequestListService;
use Illuminate\Http\Request;

class LeaveRequestExportController extends Controller
{
    public function __invoke(Request $request, LeaveRequestListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
