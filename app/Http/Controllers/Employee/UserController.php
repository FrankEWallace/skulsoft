<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\UserRequest;
use App\Http\Requests\Employee\UserUpdateRequest;
use App\Models\Employee\Employee;
use App\Services\Employee\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        // $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function confirm(Request $request, string $employee, UserService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return $service->confirm($request, $employee);
    }

    public function index(string $employee, UserService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return response()->ok($service->fetch($employee));
    }

    public function create(UserRequest $request, string $employee, UserService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('update', $employee);

        $service->create($request, $employee);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('contact.login.login')]),
        ]);
    }

    public function update(UserUpdateRequest $request, string $employee, UserService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('update', $employee);

        $employee->load('contact.user');

        $this->denyAdmin($employee->contact?->user);

        $service->update($request, $employee);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('contact.login.login')]),
        ]);
    }

    public function updateCurrentPeriod(Request $request, string $employee, UserService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('update', $employee);

        $employee->load('contact.user');

        $this->denyAdmin($employee->contact?->user);

        $service->updateCurrentPeriod($request, $employee);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.period.current_period')]),
        ]);
    }
}
