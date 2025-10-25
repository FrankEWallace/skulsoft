<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\AttendanceRequest;
use App\Services\Exam\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exam-schedule:read')->only(['preRequisite', 'fetch']);
        $this->middleware('permission:exam:marks-record|exam:subject-incharge-wise-marks-record')->only(['store', 'remove']);
    }

    public function preRequisite(Request $request, AttendanceService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, AttendanceService $service)
    {
        return $service->fetch($request);
    }

    public function store(AttendanceRequest $request, AttendanceService $service)
    {
        $service->store($request);

        return response()->success([
            'message' => trans('global.stored', ['attribute' => trans('student.attendance.attendance')]),
        ]);
    }

    public function remove(Request $request, AttendanceService $service)
    {
        $service->remove($request);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('student.attendance.attendance')]),
        ]);
    }
}
