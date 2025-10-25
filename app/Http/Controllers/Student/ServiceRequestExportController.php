<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\ServiceRequestListService;
use Illuminate\Http\Request;

class ServiceRequestExportController extends Controller
{
    public function __invoke(Request $request, ServiceRequestListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
