<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\EmployeeListService;
use Illuminate\Http\Request;

class EmployeeExportController extends Controller
{
    public function __invoke(Request $request, EmployeeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
