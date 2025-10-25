<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\SyllabusRequest;
use App\Http\Resources\Resource\SyllabusResource;
use App\Models\Resource\Syllabus;
use App\Services\Resource\SyllabusListService;
use App\Services\Resource\SyllabusService;
use Illuminate\Http\Request;

class SyllabusController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, SyllabusService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, SyllabusListService $service)
    {
        $this->authorize('viewAny', Syllabus::class);

        return $service->paginate($request);
    }

    public function store(SyllabusRequest $request, SyllabusService $service)
    {
        $this->authorize('create', Syllabus::class);

        $syllabus = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('resource.syllabus.syllabus')]),
            'syllabus' => SyllabusResource::make($syllabus),
        ]);
    }

    public function show(string $syllabus, SyllabusService $service)
    {
        $syllabus = Syllabus::findByUuidOrFail($syllabus);

        $this->authorize('view', $syllabus);

        $syllabus->load(['records.subject', 'records.batch.course', 'employee' => fn ($q) => $q->summary(), 'units', 'media']);

        return SyllabusResource::make($syllabus);
    }

    public function update(SyllabusRequest $request, string $syllabus, SyllabusService $service)
    {
        $syllabus = Syllabus::findByUuidOrFail($syllabus);

        $this->authorize('update', $syllabus);

        $service->update($request, $syllabus);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('resource.syllabus.syllabus')]),
        ]);
    }

    public function destroy(string $syllabus, SyllabusService $service)
    {
        $syllabus = Syllabus::findByUuidOrFail($syllabus);

        $this->authorize('delete', $syllabus);

        $service->deletable($syllabus);

        $syllabus->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('resource.syllabus.syllabus')]),
        ]);
    }

    public function downloadMedia(string $syllabus, string $uuid, SyllabusService $service)
    {
        $syllabus = Syllabus::findByUuidOrFail($syllabus);

        $this->authorize('view', $syllabus);

        return $syllabus->downloadMedia($uuid);
    }
}
