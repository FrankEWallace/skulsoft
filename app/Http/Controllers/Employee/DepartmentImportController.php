<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\DepartmentImportService;
use Illuminate\Http\Request;

class DepartmentImportController extends Controller
{
    public function __invoke(Request $request, DepartmentImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('employee.department.department')]),
        ]);
    }
}
