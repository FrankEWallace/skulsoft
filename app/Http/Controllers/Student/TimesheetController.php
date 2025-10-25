<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\TimesheetRequest;
use App\Http\Resources\Student\TimesheetResource;
use App\Models\Student\Timesheet;
use App\Services\Student\TimesheetListService;
use App\Services\Student\TimesheetService;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function __construct()
    {
        // $this->middleware('feature.available:student.enable_timesheet');
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:student:manage-timesheet')->except(['index']);
    }

    public function index(Request $request, TimesheetListService $service)
    {
        return $service->paginate($request);
    }

    public function store(TimesheetRequest $request, TimesheetService $service)
    {
        $timesheet = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.timesheet.timesheet')]),
            'attendance_type' => TimesheetResource::make($timesheet),
        ]);
    }

    public function show(string $timesheet, TimesheetService $service)
    {
        $timesheet = Timesheet::findByUuidOrFail($timesheet);

        return TimesheetResource::make($timesheet);
    }

    public function update(TimesheetRequest $request, string $timesheet, TimesheetService $service)
    {
        $timesheet = Timesheet::findByUuidOrFail($timesheet);

        $service->update($request, $timesheet);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.timesheet.timesheet')]),
        ]);
    }

    public function destroy(string $timesheet, TimesheetService $service)
    {
        $timesheet = Timesheet::findByUuidOrFail($timesheet);

        $service->deletable($timesheet);

        $timesheet->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.timesheet.timesheet')]),
        ]);
    }
}
