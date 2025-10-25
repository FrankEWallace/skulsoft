<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee\Payroll\SalaryTemplate;
use App\Services\Employee\Payroll\SalaryTemplateActionService;
use Illuminate\Http\Request;

class SalaryTemplateActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin')->only('forceUpdate');
    }

    public function forceUpdate(Request $request, string $uuid, SalaryTemplateActionService $service)
    {
        $salaryTemplate = SalaryTemplate::findByUuidOrFail($uuid);

        $service->forceUpdate($request, $salaryTemplate);

        return response()->json([
            'message' => __('global.updated', [
                'attribute' => trans('employee.payroll.salary_template.salary_template'),
            ]),
        ]);
    }
}
