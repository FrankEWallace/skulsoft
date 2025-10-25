<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\RecordListService;
use Illuminate\Http\Request;

class RecordExportController extends Controller
{
    public function __invoke(Request $request, string $employee, RecordListService $service)
    {
        $employee = Employee::findDetailByUuidOrFail($employee);

        $list = $service->list($request, $employee);

        return $service->export($list);
    }
}
