<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\AccountRequest;
use App\Http\Resources\Employee\AccountResource;
use App\Models\Employee\Employee;
use App\Services\Employee\AccountListService;
use App\Services\Employee\AccountService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $employee, AccountService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $employee, AccountListService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return $service->paginate($request, $employee);
    }

    public function store(AccountRequest $request, string $employee, AccountService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $account = $service->create($request, $employee);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.account.account')]),
            'account' => AccountResource::make($account),
        ]);
    }

    public function show(string $employee, string $account, AccountService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $account = $service->findByUuidOrFail($employee, $account);

        $account->load('media');

        return AccountResource::make($account);
    }

    public function update(AccountRequest $request, string $employee, string $account, AccountService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $account = $service->findByUuidOrFail($employee, $account);

        $service->update($request, $employee, $account);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.account.account')]),
        ]);
    }

    public function destroy(string $employee, string $account, AccountService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('manageEmployeeRecord', $employee);

        $account = $service->findByUuidOrFail($employee, $account);

        $service->deletable($employee, $account);

        $account->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.account.account')]),
        ]);
    }

    public function downloadMedia(string $employee, string $account, string $uuid, AccountService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $account = $service->findByUuidOrFail($employee, $account);

        return $account->downloadMedia($uuid);
    }
}
