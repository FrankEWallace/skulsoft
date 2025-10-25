<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ExperienceRequest;
use App\Http\Resources\Employee\ExperienceResource;
use App\Models\Employee\Employee;
use App\Services\Employee\ExperienceListService;
use App\Services\Employee\ExperienceService;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $employee, ExperienceService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $employee, ExperienceListService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return $service->paginate($request, $employee);
    }

    public function store(ExperienceRequest $request, string $employee, ExperienceService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $experience = $service->create($request, $employee);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.experience.experience')]),
            'experience' => ExperienceResource::make($experience),
        ]);
    }

    public function show(string $employee, string $experience, ExperienceService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $experience = $service->findByUuidOrFail($employee, $experience);

        $experience->load('employmentType', 'media');

        return ExperienceResource::make($experience);
    }

    public function update(ExperienceRequest $request, string $employee, string $experience, ExperienceService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $experience = $service->findByUuidOrFail($employee, $experience);

        $service->update($request, $employee, $experience);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.experience.experience')]),
        ]);
    }

    public function destroy(string $employee, string $experience, ExperienceService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('manageEmployeeRecord', $employee);

        $experience = $service->findByUuidOrFail($employee, $experience);

        $service->deletable($employee, $experience);

        $experience->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.experience.experience')]),
        ]);
    }

    public function downloadMedia(string $employee, string $experience, string $uuid, ExperienceService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $experience = $service->findByUuidOrFail($employee, $experience);

        $this->authorize('view', $employee);

        return $experience->downloadMedia($uuid);
    }
}
