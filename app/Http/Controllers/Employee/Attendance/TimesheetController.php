<?php

namespace App\Http\Controllers\Employee\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Attendance\TimesheetRequest;
use App\Http\Resources\Employee\Attendance\TimesheetResource;
use App\Models\Employee\Attendance\Timesheet;
use App\Services\Employee\Attendance\TimesheetListService;
use App\Services\Employee\Attendance\TimesheetService;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function index(Request $request, TimesheetListService $service)
    {
        $this->authorize('viewAny', Timesheet::class);

        return $service->paginate($request);
    }

    public function store(TimesheetRequest $request, TimesheetService $service)
    {
        $this->authorize('create', Timesheet::class);

        $timesheet = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.attendance.timesheet.timesheet')]),
            'attendance_type' => TimesheetResource::make($timesheet),
        ]);
    }

    public function show(string $timesheet, TimesheetService $service)
    {
        $timesheet = Timesheet::findByUuidOrFail($timesheet);

        $this->authorize('view', $timesheet);

        return TimesheetResource::make($timesheet);
    }

    public function update(TimesheetRequest $request, string $timesheet, TimesheetService $service)
    {
        $timesheet = Timesheet::findByUuidOrFail($timesheet);

        $this->authorize('update', $timesheet);

        $service->update($request, $timesheet);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.attendance.timesheet.timesheet')]),
        ]);
    }

    public function destroy(string $timesheet, TimesheetService $service)
    {
        $timesheet = Timesheet::findByUuidOrFail($timesheet);

        $this->authorize('delete', $timesheet);

        $service->deletable($timesheet);

        $timesheet->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.attendance.timesheet.timesheet')]),
        ]);
    }
}
