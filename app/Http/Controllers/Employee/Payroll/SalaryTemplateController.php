<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Payroll\SalaryTemplateRequest;
use App\Http\Resources\Employee\Payroll\SalaryTemplateResource;
use App\Models\Employee\Payroll\SalaryTemplate;
use App\Services\Employee\Payroll\SalaryTemplateListService;
use App\Services\Employee\Payroll\SalaryTemplateService;
use Illuminate\Http\Request;

class SalaryTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, SalaryTemplateService $service)
    {
        $this->authorize('preRequisite', SalaryTemplate::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, SalaryTemplateListService $service)
    {
        $this->authorize('viewAny', SalaryTemplate::class);

        return $service->paginate($request);
    }

    public function store(SalaryTemplateRequest $request, SalaryTemplateService $service)
    {
        $this->authorize('create', SalaryTemplate::class);

        $salaryTemplate = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.payroll.salary_template.salary_template')]),
            'salary_template' => SalaryTemplateResource::make($salaryTemplate),
        ]);
    }

    public function show(SalaryTemplate $salaryTemplate, SalaryTemplateService $service)
    {
        $salaryTemplate->load([
            'records' => function ($q) {
                $q->orderBy('position', 'asc');
            },
            'records.payHead',
            'records.attendanceType',
        ]);

        $this->authorize('view', $salaryTemplate);

        return SalaryTemplateResource::make($salaryTemplate);
    }

    public function update(SalaryTemplateRequest $request, SalaryTemplate $salaryTemplate, SalaryTemplateService $service)
    {
        $this->authorize('update', $salaryTemplate);

        $service->update($request, $salaryTemplate);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.payroll.salary_template.salary_template')]),
        ]);
    }

    public function destroy(SalaryTemplate $salaryTemplate, SalaryTemplateService $service)
    {
        $this->authorize('delete', $salaryTemplate);

        $service->deletable($salaryTemplate);

        $salaryTemplate->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.payroll.salary_template.salary_template')]),
        ]);
    }
}
