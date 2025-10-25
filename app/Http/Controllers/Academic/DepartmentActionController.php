<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\Department;
use App\Services\Academic\DepartmentActionService;
use Illuminate\Http\Request;

class DepartmentActionController extends Controller
{
    public function updateConfig(Request $request, string $department, DepartmentActionService $service)
    {
        $department = Department::findByUuidOrFail($department);

        $service->updateConfig($request, $department);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.department.department')]),
        ]);
    }

    public function reorder(Request $request, DepartmentActionService $service)
    {
        $menu = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.department.department')]),
        ]);
    }
}
