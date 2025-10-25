<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Services\Employee\Payroll\PayHeadActionService;
use Illuminate\Http\Request;

class PayHeadActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payroll:config')->only(['reorder']);
    }

    public function reorder(Request $request, PayHeadActionService $service)
    {
        $payHead = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.payroll.pay_head.pay_head')]),
        ]);
    }
}
