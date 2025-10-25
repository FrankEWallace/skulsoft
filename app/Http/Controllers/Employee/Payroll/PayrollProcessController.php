<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Payroll\PayrollProcessRequest;
use App\Models\Employee\Payroll\Payroll;
use App\Services\Employee\Payroll\PayrollProcessService;
use Illuminate\Http\Request;

class PayrollProcessController extends Controller
{
    public function process(Request $request, string $uuid, PayrollProcessService $service)
    {
        $payroll = Payroll::findDetailByUuidOrFail($uuid);

        $this->authorize('update', $payroll);

        $service->process($request, $payroll);

        return response()->success([
            'message' => trans('global.processed', ['attribute' => trans('employee.payroll.payroll')]),
        ]);
    }

    public function bulkProcess(PayrollProcessRequest $request, PayrollProcessService $service)
    {
        $this->authorize('process', Payroll::class);

        $batchUuid = $service->bulkProcess($request);

        return response()->success([
            'message' => trans('employee.payroll.under_process'),
            'batch_uuid' => $batchUuid,
        ]);
    }
}
