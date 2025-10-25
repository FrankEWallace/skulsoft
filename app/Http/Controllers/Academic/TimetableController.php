<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\TimetableRequest;
use App\Http\Resources\Academic\TimetableResource;
use App\Models\Academic\Timetable;
use App\Services\Academic\TimetableListService;
use App\Services\Academic\TimetableService;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function preRequisite(TimetableService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, TimetableListService $service)
    {
        $this->authorize('viewAny', Timetable::class);

        return $service->paginate($request);
    }

    public function store(TimetableRequest $request, TimetableService $service)
    {
        $this->authorize('create', Timetable::class);

        $timetable = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.timetable.timetable')]),
            'timetable' => TimetableResource::make($timetable),
        ]);
    }

    public function export(Request $request, string $timetable, TimetableService $service)
    {
        $timetable = Timetable::findByUuidOrFail($timetable);

        $this->authorize('view', $timetable);

        return $service->export($timetable);
    }

    public function exportTeacherTimetable(Request $request, TimetableService $service)
    {
        $this->authorize('exportTeacherTimetable', Timetable::class);

        return $service->exportTeacherTimetable($request);
    }

    public function show(Request $request, string $timetable, TimetableService $service): TimetableResource
    {
        $timetable = Timetable::findByUuidOrFail($timetable);

        $this->authorize('view', $timetable);

        if ($request->query('detail')) {
            return $service->getDetail($timetable);
        }

        $timetable->load(['batch', 'records.classTiming.sessions', 'room' => fn ($q) => $q->withFloorAndBlock()]);

        return TimetableResource::make($timetable);
    }

    public function update(TimetableRequest $request, string $timetable, TimetableService $service)
    {
        $timetable = Timetable::findByUuidOrFail($timetable);

        $this->authorize('update', $timetable);

        $service->update($request, $timetable);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.timetable.timetable')]),
        ]);
    }

    public function destroy(string $timetable, TimetableService $service)
    {
        $timetable = Timetable::findByUuidOrFail($timetable);

        $this->authorize('delete', $timetable);

        $service->deletable($timetable);

        $timetable->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.timetable.timetable')]),
        ]);
    }
}
