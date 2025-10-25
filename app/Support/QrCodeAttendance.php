<?php

namespace App\Support;

trait QrCodeAttendance
{
    public function isQrCodeAttendanceEnabled()
    {
        $qrCodeAttendanceEnabled = false;

        if (! config('config.employee.enable_qr_code_attendance')) {
            return false;
        }

        // if (auth()->user()->is_default) {
        //     return false;
        // }

        // if (auth()->user()->hasRole('attendance-assistant')) {
        //     return false;
        // }

        if (! auth()->user()->hasAnyRole(['guardian', 'alumni'])) {
            if (auth()->user()->hasRole('student') && config('config.student.enable_qr_code_attendance')) {
                $qrCodeAttendanceEnabled = true;
            } elseif (! auth()->user()->hasRole('student') && config('config.employee.enable_qr_code_attendance')) {
                $qrCodeAttendanceEnabled = true;
            }
        }

        return $qrCodeAttendanceEnabled;
    }
}
