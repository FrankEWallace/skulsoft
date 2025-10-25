<?php

namespace App\Http\Controllers\Employee;

use App\Enums\CustomFieldForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\EmployeeRequest;
use App\Http\Requests\Employee\EmployeeUpdateRequest;
use App\Http\Resources\Employee\EmployeeResource;
use App\Models\Employee\Employee;
use App\Services\Employee\EmployeeListService;
use App\Services\Employee\EmployeeService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, EmployeeService $service)
    {
        $this->authorize('preRequisite', Employee::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, EmployeeListService $service)
    {
        $this->authorize('viewAny', Employee::class);

        return $service->paginate($request);
    }

    public function list(Request $request, EmployeeListService $service)
    {
        $this->authorize('viewSummary', Employee::class);

        $request->merge(['basic' => true]);

        return $service->paginate($request);
    }

    public function store(EmployeeRequest $request, EmployeeService $service)
    {
        $this->authorize('create', Employee::class);

        $employee = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.employee')]),
            'employee' => EmployeeResource::make($employee),
        ]);
    }

    public function show(Request $request, string $employee)
    {
        $employee = Employee::findDetailByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $employee->load('contact.user.roles', 'contact.religion', 'contact.category', 'contact.caste', 'groups.modelGroup', 'tags');

        $request->merge([
            'has_custom_fields' => true,
            'custom_field_form' => CustomFieldForm::EMPLOYEE->value,
        ]);

        return EmployeeResource::make($employee);
    }

    public function update(EmployeeUpdateRequest $request, string $employee, EmployeeService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('update', $employee);

        $employee->load('contact');

        $service->update($request, $employee);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.employee')]),
        ]);
    }

    public function destroy(string $employee, EmployeeService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('delete', $employee);

        $employee->load('contact.user');

        $this->denyAdmin($employee->contact?->user);

        $service->deletable($employee);

        $employee->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.employee')]),
        ]);
    }
}
