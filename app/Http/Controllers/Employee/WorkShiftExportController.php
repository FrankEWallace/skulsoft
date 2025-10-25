<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\WorkShiftListService;
use Illuminate\Http\Request;

class WorkShiftExportController extends Controller
{
    public function __invoke(Request $request, string $employee, WorkShiftListService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $list = $service->list($request, $employee);

        return $service->export($list);
    }
}
