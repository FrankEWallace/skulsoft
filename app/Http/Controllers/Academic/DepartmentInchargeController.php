<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\DepartmentInchargeRequest;
use App\Http\Resources\Academic\DepartmentInchargeResource;
use App\Models\Incharge;
use App\Services\Academic\DepartmentInchargeListService;
use App\Services\Academic\DepartmentInchargeService;
use Illuminate\Http\Request;

class DepartmentInchargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:academic-department:manage');
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, DepartmentInchargeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, DepartmentInchargeListService $service)
    {
        $this->authorize('viewAny', Incharge::class, 'department');

        return $service->paginate($request);
    }

    public function store(DepartmentInchargeRequest $request, DepartmentInchargeService $service)
    {
        $departmentIncharge = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.department_incharge.department_incharge')]),
            'department_incharge' => DepartmentInchargeResource::make($departmentIncharge),
        ]);
    }

    public function show(string $departmentIncharge, DepartmentInchargeService $service)
    {
        $departmentIncharge = Incharge::findByUuidOrFail($departmentIncharge);

        $departmentIncharge->load(['model', 'employee' => fn ($q) => $q->summary()]);

        return DepartmentInchargeResource::make($departmentIncharge);
    }

    public function update(DepartmentInchargeRequest $request, string $departmentIncharge, DepartmentInchargeService $service)
    {
        $departmentIncharge = Incharge::findByUuidOrFail($departmentIncharge);

        $service->update($request, $departmentIncharge, 'department');

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.department_incharge.department_incharge')]),
        ]);
    }

    public function destroy(string $departmentIncharge, DepartmentInchargeService $service)
    {
        $departmentIncharge = Incharge::findByUuidOrFail($departmentIncharge);

        $service->deletable($departmentIncharge);

        $departmentIncharge->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.department_incharge.department_incharge')]),
        ]);
    }
}
