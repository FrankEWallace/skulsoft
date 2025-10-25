<?php

namespace App\Http\Controllers\Transport\Report;

use App\Http\Controllers\Controller;
use App\Services\Transport\Report\RouteWiseStudentListService;
use Illuminate\Http\Request;

class RouteWiseStudentExportController extends Controller
{
    public function __invoke(Request $request, RouteWiseStudentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
