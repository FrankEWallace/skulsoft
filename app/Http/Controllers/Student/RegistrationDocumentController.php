<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\RegistrationDocumentRequest;
use App\Http\Resources\Student\RegistrationDocumentResource;
use App\Models\Student\Registration;
use App\Services\Student\RegistrationDocumentService;
use Illuminate\Http\Request;

class RegistrationDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $registration, RegistrationDocumentService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $registration, RegistrationDocumentService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        return $service->list($request, $registration);
    }

    public function store(RegistrationDocumentRequest $request, string $registration, RegistrationDocumentService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('update', $registration);

        $document = $service->create($request, $registration);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.document.document')]),
            'document' => RegistrationDocumentResource::make($document),
        ]);
    }

    public function show(string $registration, string $document, RegistrationDocumentService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        $document = $service->findByUuidOrFail($registration, $document);

        $document->load('level', 'media');

        return RegistrationDocumentResource::make($document);
    }

    public function update(RegistrationDocumentRequest $request, string $registration, string $document, RegistrationDocumentService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('update', $registration);

        $document = $service->findByUuidOrFail($registration, $document);

        $service->update($request, $registration, $document);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.document.document')]),
        ]);
    }

    public function destroy(string $registration, string $document, RegistrationDocumentService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('update', $registration);

        $document = $service->findByUuidOrFail($registration, $document);

        $service->deletable($registration, $document);

        $document->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.document.document')]),
        ]);
    }

    public function downloadMedia(string $registration, string $document, string $uuid, RegistrationDocumentService $service)
    {
        $registration = Registration::findByUuidOrFail($registration);

        $this->authorize('view', $registration);

        $document = $service->findByUuidOrFail($registration, $document);

        return $document->downloadMedia($uuid);
    }
}
