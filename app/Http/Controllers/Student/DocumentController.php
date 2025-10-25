<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\DocumentRequest;
use App\Http\Resources\Student\DocumentResource;
use App\Models\Student\Student;
use App\Services\Student\DocumentListService;
use App\Services\Student\DocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $student, DocumentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $student, DocumentListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function store(DocumentRequest $request, string $student, DocumentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $document = $service->create($request, $student);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.document.document')]),
            'document' => DocumentResource::make($document),
        ]);
    }

    public function show(string $student, string $document, DocumentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $document = $service->findByUuidOrFail($student, $document);

        $document->load('type', 'media');

        return DocumentResource::make($document);
    }

    public function update(DocumentRequest $request, string $student, string $document, DocumentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $document = $service->findByUuidOrFail($student, $document);

        $service->update($request, $student, $document);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.document.document')]),
        ]);
    }

    public function destroy(string $student, string $document, DocumentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $document = $service->findByUuidOrFail($student, $document);

        $service->deletable($student, $document);

        $document->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.document.document')]),
        ]);
    }

    public function downloadMedia(string $student, string $document, string $uuid, DocumentService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $document = $service->findByUuidOrFail($student, $document);

        return $document->downloadMedia($uuid);
    }
}
