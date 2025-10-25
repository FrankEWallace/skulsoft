<?php

namespace App\Http\Controllers\Employee\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Employee\Attendance\TimesheetImportService;
use Illuminate\Http\Request;

class TimesheetImportController extends Controller
{
    public function __invoke(Request $request, TimesheetImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('employee.attendance.timesheet.timesheet')]),
        ]);
    }
}
