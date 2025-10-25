<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\DepartmentRequest;
use App\Http\Resources\Academic\DepartmentResource;
use App\Models\Academic\Department;
use App\Services\Academic\DepartmentListService;
use App\Services\Academic\DepartmentService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function preRequisite(DepartmentService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, DepartmentListService $service)
    {
        return $service->paginate($request);
    }

    public function store(DepartmentRequest $request, DepartmentService $service)
    {
        $department = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.department.department')]),
            'department' => DepartmentResource::make($department),
        ]);
    }

    public function show(string $department, DepartmentService $service): DepartmentResource
    {
        $department = Department::findByUuidOrFail($department);

        $department->load('programs');

        return DepartmentResource::make($department);
    }

    public function update(DepartmentRequest $request, string $department, DepartmentService $service)
    {
        $department = Department::findByUuidOrFail($department);

        $service->update($request, $department);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.department.department')]),
        ]);
    }

    public function destroy(string $department, DepartmentService $service)
    {
        $department = Department::findByUuidOrFail($department);

        $service->deletable($department);

        $department->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.department.department')]),
        ]);
    }
}
