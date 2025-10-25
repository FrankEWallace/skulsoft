<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Payroll\SalaryStructureRequest;
use App\Http\Resources\Employee\Payroll\SalaryStructureResource;
use App\Models\Employee\Payroll\SalaryStructure;
use App\Services\Employee\Payroll\SalaryStructureListService;
use App\Services\Employee\Payroll\SalaryStructureService;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, SalaryStructureService $service)
    {
        $this->authorize('preRequisite', SalaryStructure::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, SalaryStructureListService $service)
    {
        $this->authorize('viewAny', SalaryStructure::class);

        return $service->paginate($request);
    }

    public function store(SalaryStructureRequest $request, SalaryStructureService $service)
    {
        $this->authorize('create', SalaryStructure::class);

        $salaryStructure = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.payroll.salary_structure.salary_structure')]),
            'salary_structure' => SalaryStructureResource::make($salaryStructure),
        ]);
    }

    public function show(Request $request, string $salaryStructure, SalaryStructureService $service)
    {
        $salaryStructure = SalaryStructure::findDetailByUuidOrFail($salaryStructure);

        $this->authorize('view', $salaryStructure);

        $salaryStructure->load('template.records.payHead');

        $salaryStructure->new_records = $service->getRecords($salaryStructure);

        return SalaryStructureResource::make($salaryStructure);
    }

    public function update(SalaryStructureRequest $request, string $salaryStructure, SalaryStructureService $service)
    {
        $salaryStructure = SalaryStructure::findDetailByUuidOrFail($salaryStructure);

        $this->authorize('update', $salaryStructure);

        $service->update($request, $salaryStructure);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.payroll.salary_structure.salary_structure')]),
        ]);
    }

    public function destroy(string $salaryStructure, SalaryStructureService $service)
    {
        $salaryStructure = SalaryStructure::findByUuidOrFail($salaryStructure);

        $this->authorize('delete', $salaryStructure);

        $service->deletable($salaryStructure);

        $salaryStructure->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.payroll.salary_structure.salary_structure')]),
        ]);
    }
}
