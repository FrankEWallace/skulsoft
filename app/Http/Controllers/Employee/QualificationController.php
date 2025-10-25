<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\QualificationRequest;
use App\Http\Resources\Employee\QualificationResource;
use App\Models\Employee\Employee;
use App\Services\Employee\QualificationListService;
use App\Services\Employee\QualificationService;
use Illuminate\Http\Request;

class QualificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $employee, QualificationService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $employee, QualificationListService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return $service->paginate($request, $employee);
    }

    public function store(QualificationRequest $request, string $employee, QualificationService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $qualification = $service->create($request, $employee);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.qualification.qualification')]),
            'qualification' => QualificationResource::make($qualification),
        ]);
    }

    public function show(string $employee, string $qualification, QualificationService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $qualification = $service->findByUuidOrFail($employee, $qualification);

        $qualification->load('level', 'media');

        return QualificationResource::make($qualification);
    }

    public function update(QualificationRequest $request, string $employee, string $qualification, QualificationService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $qualification = $service->findByUuidOrFail($employee, $qualification);

        $service->update($request, $employee, $qualification);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.qualification.qualification')]),
        ]);
    }

    public function destroy(string $employee, string $qualification, QualificationService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('manageEmployeeRecord', $employee);

        $qualification = $service->findByUuidOrFail($employee, $qualification);

        $service->deletable($employee, $qualification);

        $qualification->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.qualification.qualification')]),
        ]);
    }

    public function downloadMedia(string $employee, string $qualification, string $uuid, QualificationService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $qualification = $service->findByUuidOrFail($employee, $qualification);

        return $qualification->downloadMedia($uuid);
    }
}
