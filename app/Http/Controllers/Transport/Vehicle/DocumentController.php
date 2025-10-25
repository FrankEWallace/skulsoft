<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\Vehicle\DocumentRequest;
use App\Http\Resources\Transport\Vehicle\DocumentResource;
use App\Services\Transport\Vehicle\DocumentListService;
use App\Services\Transport\Vehicle\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, DocumentService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, DocumentListService $service)
    {
        Gate::authorize('vehicle-document:read');

        return $service->paginate($request);
    }

    public function store(DocumentRequest $request, DocumentService $service)
    {
        Gate::authorize('vehicle-document:create');

        $vehicleDocument = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.vehicle.document.document')]),
            'vehicle' => DocumentResource::make($vehicleDocument),
        ]);
    }

    public function show(string $vehicleDocument, DocumentService $service)
    {
        $vehicleDocument = $service->findByUuidOrFail($vehicleDocument);

        Gate::authorize('vehicle-document:read', $vehicleDocument);

        $vehicleDocument->load('documentable', 'type', 'media');

        return DocumentResource::make($vehicleDocument);
    }

    public function update(DocumentRequest $request, string $vehicleDocument, DocumentService $service)
    {
        $vehicleDocument = $service->findByUuidOrFail($vehicleDocument);

        Gate::authorize('vehicle-document:edit', $vehicleDocument);

        $service->update($request, $vehicleDocument);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.vehicle.document.document')]),
        ]);
    }

    public function destroy(string $vehicleDocument, DocumentService $service)
    {
        $vehicleDocument = $service->findByUuidOrFail($vehicleDocument);

        Gate::authorize('vehicle-document:delete', $vehicleDocument);

        $service->deletable($vehicleDocument);

        $vehicleDocument->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.vehicle.document.document')]),
        ]);
    }

    public function downloadMedia(string $vehicleDocument, string $uuid, DocumentService $service)
    {
        $vehicleDocument = $service->findByUuidOrFail($vehicleDocument);

        Gate::authorize('vehicle-document:read', $vehicleDocument);

        return $vehicleDocument->downloadMedia($uuid);
    }
}
