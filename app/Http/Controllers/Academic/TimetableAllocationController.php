<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\TimetableAllocationRequest;
use App\Models\Academic\Timetable;
use App\Services\Academic\TimetableAllocationService;

class TimetableAllocationController extends Controller
{
    public function preRequisite(string $timetable, TimetableAllocationService $service)
    {
        $timetable = Timetable::query()
            ->findByUuidOrFail($timetable);

        return $service->preRequisite($timetable);
    }

    public function allocation(TimetableAllocationRequest $request, string $timetable, TimetableAllocationService $service)
    {
        $timetable = Timetable::query()
            ->findByUuidOrFail($timetable);

        $service->allocation($request, $timetable);

        return response()->success([
            'message' => trans('global.allocated', ['attribute' => trans('academic.timetable.timetable')]),
        ]);
    }
}
