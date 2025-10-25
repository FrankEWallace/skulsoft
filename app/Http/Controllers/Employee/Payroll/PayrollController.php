<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Payroll\PayrollRequest;
use App\Http\Resources\Employee\Payroll\PayrollResource;
use App\Models\Employee\Payroll\Payroll;
use App\Services\Employee\Payroll\PayrollListService;
use App\Services\Employee\Payroll\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, PayrollService $service)
    {
        $this->authorize('preRequisite', Payroll::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, PayrollListService $service)
    {
        $this->authorize('viewAny', Payroll::class);

        return $service->paginate($request);
    }

    public function fetch(PayrollRequest $request, PayrollService $service)
    {
        $this->authorize('create', Payroll::class);

        return $service->fetch($request);
    }

    public function store(PayrollRequest $request, PayrollService $service)
    {
        $this->authorize('create', Payroll::class);

        $payroll = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.payroll.payroll')]),
            'payroll' => PayrollResource::make($payroll),
        ]);
    }

    public function export(Request $request, string $payroll, PayrollService $service)
    {
        $payroll = Payroll::findDetailByUuidOrFail($payroll);

        $this->authorize('view', $payroll);

        $service->isProcessed($payroll);

        $attendanceSummary = $payroll->getAttendanceSummary();

        $contact = $payroll->employee->contact;

        $account = $contact->accounts()->first();

        return view()->first([
            'print.custom.employee.payroll.salary-slip',
            'print.employee.payroll.salary-slip',
        ], compact('payroll', 'attendanceSummary', 'account'));
    }

    public function show(Request $request, string $payroll, PayrollService $service)
    {
        $payroll = Payroll::findDetailByUuidOrFail($payroll);

        $this->authorize('view', $payroll);

        $service->isProcessed($payroll);

        $request->merge(['show_attendance_summary' => true]);

        return PayrollResource::make($payroll);
    }

    public function update(PayrollRequest $request, string $payroll, PayrollService $service)
    {
        $payroll = Payroll::findDetailByUuidOrFail($payroll);

        $this->authorize('update', $payroll);

        $service->isProcessed($payroll);

        $service->update($request, $payroll);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.payroll.payroll')]),
        ]);
    }

    public function destroy(string $payroll, PayrollService $service)
    {
        $payroll = Payroll::findByUuidOrFail($payroll);

        $this->authorize('delete', $payroll);

        $service->deletable($payroll);

        $payroll->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.payroll.payroll')]),
        ]);
    }
}
