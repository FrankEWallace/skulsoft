<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\InchargeListService;
use Illuminate\Http\Request;

class InchargeExportController extends Controller
{
    public function __invoke(Request $request, string $employee, InchargeListService $service)
    {
        $employee = Employee::findDetailByUuidOrFail($employee);

        $list = $service->list($request, $employee);

        return $service->export($list);
    }
}
