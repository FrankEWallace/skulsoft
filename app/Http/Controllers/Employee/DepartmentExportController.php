<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\DepartmentListService;
use Illuminate\Http\Request;

class DepartmentExportController extends Controller
{
    public function __invoke(Request $request, DepartmentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
