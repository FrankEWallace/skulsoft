<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AttendanceRequest;
use App\Models\Student\Student;
use App\Services\Student\AttendanceListService;
use App\Services\Student\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function preRequisite(Request $request, AttendanceService $service)
    {
        $this->authorize('listAttendance', Student::class);

        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, AttendanceListService $service)
    {
        $this->authorize('listAttendance', Student::class);

        return $service->paginate($request);
    }

    public function store(AttendanceRequest $request, AttendanceService $service)
    {
        $this->authorize('markAttendance', Student::class);

        $service->store($request);

        return response()->success([
            'message' => trans('student.attendance.marked'),
        ]);
    }

    public function remove(AttendanceRequest $request, AttendanceService $service)
    {
        $this->authorize('markAttendance', Student::class);

        $service->remove($request);

        return response()->success([
            'message' => trans('student.attendance.removed'),
        ]);
    }

    public function sendNotification(Request $request, AttendanceService $service)
    {
        $this->authorize('markAttendance', Student::class);

        $service->sendNotification($request);

        return response()->success([
            'message' => trans('global.sent', ['attribute' => trans('general.notification.notification')]),
        ]);
    }
}
