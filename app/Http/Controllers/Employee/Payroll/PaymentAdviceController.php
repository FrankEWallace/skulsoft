<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Services\Employee\Payroll\PaymentAdviceService;
use Illuminate\Http\Request;

class PaymentAdviceController extends Controller
{
    public function __invoke(Request $request, PaymentAdviceService $service)
    {
        return $service->generate($request);
    }
}
