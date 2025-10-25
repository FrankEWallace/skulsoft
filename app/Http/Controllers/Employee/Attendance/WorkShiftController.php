<?php

namespace App\Http\Controllers\Employee\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Attendance\WorkShiftRequest;
use App\Http\Resources\Employee\Attendance\WorkShiftResource;
use App\Models\Employee\Attendance\WorkShift;
use App\Services\Employee\Attendance\WorkShiftListService;
use App\Services\Employee\Attendance\WorkShiftService;
use Illuminate\Http\Request;

class WorkShiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, WorkShiftService $service)
    {
        $this->authorize('preRequisite', WorkShift::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, WorkShiftListService $service)
    {
        $this->authorize('viewAny', WorkShift::class);

        return $service->paginate($request);
    }

    public function store(WorkShiftRequest $request, WorkShiftService $service)
    {
        $this->authorize('create', WorkShift::class);

        $workShift = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.attendance.work_shift.work_shift')]),
            'attendance_type' => WorkShiftResource::make($workShift),
        ]);
    }

    public function show(WorkShift $workShift, WorkShiftService $service)
    {
        $this->authorize('view', $workShift);

        return WorkShiftResource::make($workShift);
    }

    public function update(WorkShiftRequest $request, WorkShift $workShift, WorkShiftService $service)
    {
        $this->authorize('update', $workShift);

        $service->update($request, $workShift);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.attendance.work_shift.work_shift')]),
        ]);
    }

    public function destroy(WorkShift $workShift, WorkShiftService $service)
    {
        $this->authorize('delete', $workShift);

        $service->deletable($workShift);

        $workShift->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.attendance.work_shift.work_shift')]),
        ]);
    }
}
