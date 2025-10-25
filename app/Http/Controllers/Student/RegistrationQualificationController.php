<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\RegistrationQualificationRequest;
use App\Http\Resources\Student\RegistrationQualificationResource;
use App\Models\Student\Registration;
use App\Services\Student\RegistrationQualificationService;
use Illuminate\Http\Request;

class RegistrationQualificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $registration, RegistrationQualificationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $registration, RegistrationQualificationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        return $service->list($request, $registration);
    }

    public function store(RegistrationQualificationRequest $request, string $registration, RegistrationQualificationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('update', $registration);

        $qualification = $service->create($request, $registration);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.qualification.qualification')]),
            'qualification' => RegistrationQualificationResource::make($qualification),
        ]);
    }

    public function show(string $registration, string $qualification, RegistrationQualificationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        $qualification = $service->findByUuidOrFail($registration, $qualification);

        $qualification->load('level', 'media');

        return RegistrationQualificationResource::make($qualification);
    }

    public function update(RegistrationQualificationRequest $request, string $registration, string $qualification, RegistrationQualificationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('update', $registration);

        $qualification = $service->findByUuidOrFail($registration, $qualification);

        $service->update($request, $registration, $qualification);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.qualification.qualification')]),
        ]);
    }

    public function destroy(string $registration, string $qualification, RegistrationQualificationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('update', $registration);

        $qualification = $service->findByUuidOrFail($registration, $qualification);

        $service->deletable($registration, $qualification);

        $qualification->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.qualification.qualification')]),
        ]);
    }

    public function downloadMedia(string $registration, string $qualification, string $uuid, RegistrationQualificationService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        $qualification = $service->findByUuidOrFail($registration, $qualification);

        return $qualification->downloadMedia($uuid);
    }
}
