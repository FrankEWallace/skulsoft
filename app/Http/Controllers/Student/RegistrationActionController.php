<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\RegistrationActionRequest;
use App\Models\Student\Registration;
use App\Services\Contact\PhotoService;
use App\Services\Student\RegistrationActionService;
use Illuminate\Http\Request;

class RegistrationActionController extends Controller
{
    public function preRequisite(Request $request, Registration $registration, RegistrationActionService $service)
    {
        $this->authorize('action', $registration);

        return response()->ok($service->preRequisite($request, $registration));
    }

    public function uploadPhoto(Request $request, string $registration, RegistrationActionService $service, PhotoService $photoService)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $photoService->upload($request, $registration->contact);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function removePhoto(Request $request, string $registration, RegistrationActionService $service, PhotoService $photoService)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $photoService->remove($request, $registration->contact);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function action(RegistrationActionRequest $request, Registration $registration, RegistrationActionService $service)
    {
        $this->authorize('action', $registration);

        $service->action($request, $registration);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.registration.registration')]),
        ]);
    }

    public function updateBulkAssignTo(Request $request, RegistrationActionService $service)
    {
        $this->authorize('bulkUpdate', Registration::class);

        $service->updateBulkAssignTo($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.registration.registration')]),
        ]);
    }

    public function updateBulkStage(Request $request, RegistrationActionService $service)
    {
        $this->authorize('bulkUpdate', Registration::class);

        $service->updateBulkStage($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.registration.registration')]),
        ]);
    }
}
