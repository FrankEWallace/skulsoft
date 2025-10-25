<?php

namespace App\Http\Controllers\Discipline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Discipline\IncidentRequest;
use App\Http\Resources\Discipline\IncidentResource;
use App\Models\Discipline\Incident;
use App\Services\Discipline\IncidentListService;
use App\Services\Discipline\IncidentService;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, IncidentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, IncidentListService $service)
    {
        return $service->paginate($request);
    }

    public function store(IncidentRequest $request, IncidentService $service)
    {
        $incident = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('discipline.incident.incident')]),
            'incident' => IncidentResource::make($incident),
        ]);
    }

    public function show(Request $request, string $incident, IncidentService $service)
    {
        $incident = Incident::findByUuidOrFail($incident);

        $incident->load([
            'model.contact',
            'category',
            'media',
            'user',
        ]);

        $request->merge([
            'show_details' => true,
        ]);

        return IncidentResource::make($incident);
    }

    public function update(IncidentRequest $request, string $incident, IncidentService $service)
    {
        $incident = Incident::findByUuidOrFail($incident);

        $service->update($request, $incident);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('discipline.incident.incident')]),
        ]);
    }

    public function destroy(string $incident, IncidentService $service)
    {
        $incident = Incident::findByUuidOrFail($incident);

        $service->deletable($incident);

        $incident->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('discipline.incident.incident')]),
        ]);
    }

    public function downloadMedia(string $incident, string $uuid, IncidentService $service)
    {
        $incident = Incident::findByUuidOrFail($incident);

        return $incident->downloadMedia($uuid);
    }
}
