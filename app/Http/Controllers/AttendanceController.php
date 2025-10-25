<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function fetchQrCode(Request $request, AttendanceService $service)
    {
        return $service->fetchQrCode($request);
    }

    public function markAttendance(Request $request, AttendanceService $service)
    {
        if (! auth()->user()->hasAnyRole(['admin', 'attendance-assistant'])) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        $service->markAttendance($request);

        return response()->json(['message' => trans('attendance.marked')]);
    }

    public function markAnomymousAttendance(Request $request, AttendanceService $service)
    {
        $service->markAttendance($request);

        return response()->json(['message' => trans('attendance.marked')]);
    }
}
