<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\RecordRequest;
use App\Http\Resources\Employee\RecordResource;
use App\Models\Employee\Employee;
use App\Services\Employee\RecordListService;
use App\Services\Employee\RecordService;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $employee, RecordService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('fetchEmploymentRecord', $employee);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $employee, RecordListService $service)
    {
        $employee = Employee::findDetailByUuidOrFail($employee);

        $this->authorize('fetchEmploymentRecord', $employee);

        return $service->paginate($request, $employee);
    }

    public function store(RecordRequest $request, string $employee, RecordService $service)
    {
        $employee = Employee::findDetailByUuidOrFail($employee);

        $this->authorize('manageEmploymentRecord', $employee);

        $employee->load('contact.user');

        $this->denySuperAdmin($employee->contact?->user);

        $record = $service->create($request, $employee);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.record.record')]),
            'record' => RecordResource::make($record),
        ]);
    }

    public function show(string $employee, string $record, RecordService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('fetchEmploymentRecord', $employee);

        $record = $service->findByUuidOrFail($employee, $record);

        $record->load('media');

        return RecordResource::make($record);
    }

    public function update(RecordRequest $request, string $employee, string $record, RecordService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('manageEmploymentRecord', $employee);

        $record = $service->findByUuidOrFail($employee, $record);

        $employee->load('contact.user');

        $this->denySuperAdmin($employee->contact?->user);

        $service->update($request, $employee, $record);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.record.record')]),
        ]);
    }

    public function destroy(string $employee, string $record, RecordService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('manageEmploymentRecord', $employee);

        $record = $service->findByUuidOrFail($employee, $record);

        $employee->load('contact.user');

        $this->denySuperAdmin($employee->contact?->user);

        $service->deletable($employee, $record);

        $service->delete($employee, $record);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.record.record')]),
        ]);
    }

    public function downloadMedia(string $employee, string $record, $uuid, RecordService $service)
    {
        $employee = Employee::findByUuidOrFail($employee);

        $this->authorize('fetchEmploymentRecord', $employee);

        $record = $service->findByUuidOrFail($employee, $record);

        return $record->downloadMedia($uuid);
    }
}
