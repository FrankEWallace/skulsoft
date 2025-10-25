<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\TimesheetBatchRequest;
use App\Services\Student\TimesheetBatchService;
use Illuminate\Http\Request;

class TimesheetBatchController extends Controller
{
    public function __construct()
    {
        // $this->middleware('feature.available:student.enable_timesheet');
        $this->middleware('permission:student:manage-timesheet');
    }

    public function preRequisite(Request $request, TimesheetBatchService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, TimesheetBatchService $service)
    {
        return $service->fetch($request);
    }

    public function store(TimesheetBatchRequest $request, TimesheetBatchService $service)
    {
        $service->store($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.timesheet.timesheet')]),
        ]);
    }
}
