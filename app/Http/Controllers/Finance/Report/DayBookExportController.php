<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\DayBookListService;
use Illuminate\Http\Request;

class DayBookExportController extends Controller
{
    public function __invoke(Request $request, DayBookListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
