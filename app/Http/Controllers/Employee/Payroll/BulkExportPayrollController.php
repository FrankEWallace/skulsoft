<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Services\Employee\Payroll\BulkExportPayrollService;
use Illuminate\Http\Request;

class BulkExportPayrollController extends Controller
{
    public function __invoke(Request $request, BulkExportPayrollService $service)
    {
        return $service->generate($request);
    }
}
