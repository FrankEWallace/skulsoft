<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\DocumentRequest;
use App\Http\Resources\Employee\DocumentResource;
use App\Models\Employee\Employee;
use App\Services\Employee\DocumentListService;
use App\Services\Employee\DocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $employee, DocumentService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $employee, DocumentListService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return $service->paginate($request, $employee);
    }

    public function store(DocumentRequest $request, string $employee, DocumentService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $document = $service->create($request, $employee);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.document.document')]),
            'document' => DocumentResource::make($document),
        ]);
    }

    public function show(string $employee, string $document, DocumentService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $document = $service->findByUuidOrFail($employee, $document);

        $document->load('type', 'media');

        return DocumentResource::make($document);
    }

    public function update(DocumentRequest $request, string $employee, string $document, DocumentService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfService', $employee);

        $document = $service->findByUuidOrFail($employee, $document);

        $service->update($request, $employee, $document);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.document.document')]),
        ]);
    }

    public function destroy(string $employee, string $document, DocumentService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('manageEmployeeRecord', $employee);

        $document = $service->findByUuidOrFail($employee, $document);

        $service->deletable($employee, $document);

        $document->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.document.document')]),
        ]);
    }

    public function downloadMedia(string $employee, string $document, string $uuid, DocumentService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $document = $service->findByUuidOrFail($employee, $document);

        return $document->downloadMedia($uuid);
    }
}
