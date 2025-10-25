<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Services\Employee\Payroll\SalaryStructureListService;
use Illuminate\Http\Request;

class SalaryStructureExportController extends Controller
{
    public function __invoke(Request $request, SalaryStructureListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
