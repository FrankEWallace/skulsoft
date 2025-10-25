<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\AccountActionService;
use Illuminate\Http\Request;

class AccountActionController extends Controller
{
    public function makePrimary(Request $request, string $employee, string $account, AccountActionService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $service->makePrimary($request, $employee, $account);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.account.account')]),
        ]);
    }

    public function action(Request $request, string $employee, string $account, AccountActionService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfServiceAction', $employee);

        $service->action($request, $employee, $account);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.account.account')]),
        ]);
    }
}
