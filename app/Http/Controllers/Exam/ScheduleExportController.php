<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\ScheduleListService;
use Illuminate\Http\Request;

class ScheduleExportController extends Controller
{
    public function __invoke(Request $request, ScheduleListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
