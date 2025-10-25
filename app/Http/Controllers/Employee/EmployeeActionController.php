<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\EmployeeActionService;
use Illuminate\Http\Request;

class EmployeeActionController extends Controller
{
    public function __construct()
    {
        //
    }

    public function updateTags(Request $request, string $employee, EmployeeActionService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('update', $employee);

        $service->updateTags($request, $employee);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.employee')]),
        ]);
    }

    public function updateBulkTags(Request $request, EmployeeActionService $service)
    {
        $this->authorize('bulkUpdate', Employee::class);

        $service->updateBulkTags($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.employee')]),
        ]);
    }

    public function updateBulkGroups(Request $request, EmployeeActionService $service)
    {
        $this->authorize('bulkUpdate', Employee::class);

        $service->updateBulkGroups($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.employee')]),
        ]);
    }
}
