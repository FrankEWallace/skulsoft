<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Services\Employee\Payroll\SalarySheetService;
use Illuminate\Http\Request;

class SalarySheetController extends Controller
{
    public function __invoke(Request $request, SalarySheetService $service)
    {
        return $service->generate($request);
    }
}
