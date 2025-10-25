<?php

namespace App\Services;

use App\Actions\Employee\Attendance\StoreTimesheet;
use App\Enums\Student\AttendanceSession;
use App\Helpers\CalHelper;
use App\Models\Device;
use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Employee;
use App\Models\Student\Attendance;
use App\Models\Student\Student;
use App\Support\QrCodeAttendance;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    use QrCodeAttendance;

    private function checkQrCodeAttendanceEnabled()
    {
        if (! $this->isQrCodeAttendanceEnabled()) {
            throw ValidationException::withMessages(['message' => trans('attendance.qr_code_attendance_disabled')]);
        }
    }

    public function fetchQrCode(Request $request)
    {
        $this->checkQrCodeAttendanceEnabled();

        if (auth()->user()->is_default) {
            $qrCodeText = 'super-admin';
        } elseif (auth()->user()->hasRole('attendance-assistant')) {
            $qrCodeText = 'attendance-assistant';
        } elseif (auth()->user()->hasRole('student')) {
            $qrCodeExpiryDuration = (int) config('config.student.qr_code_expiry_duration');

            $student = Student::query()
                ->auth()
                ->firstOrFail();

            $student = Student::find($student->id);

            if (! config('config.student.has_dynamic_qr_code')) {
                $qrCodeText = $student->admission->code_number;
            } else {
                if (! $student->getMeta('qr_code_attendance')) {
                    $student->setMeta([
                        'qr_code_attendance' => Str::random(16),
                        'qr_code_expiry_at' => now()->addSeconds($qrCodeExpiryDuration)->toDateTimeString(),
                    ]);
                } else {
                    $qrCodeExpiryAt = $student->getMeta('qr_code_expiry_at') ?? now()->toDateTimeString();

                    if (Carbon::parse($qrCodeExpiryAt)->isPast()) {
                        $student->setMeta([
                            'qr_code_attendance' => Str::random(16),
                            'qr_code_expiry_at' => now()->addSeconds($qrCodeExpiryDuration)->toDateTimeString(),
                        ]);
                    }
                }

                $student->save();

                $qrCodeText = $student->getMeta('qr_code_attendance');
            }
        } else {
            $qrCodeExpiryDuration = (int) config('config.employee.qr_code_expiry_duration');

            $employee = Employee::query()
                ->auth()
                ->firstOrFail();

            $employee = Employee::find($employee->id);

            if (! config('config.employee.has_dynamic_qr_code')) {
                $qrCodeText = $employee->code_number;
            } else {
                if (! $employee->getMeta('qr_code_attendance')) {
                    $employee->setMeta([
                        'qr_code_attendance' => Str::random(16),
                        'qr_code_expiry_at' => now()->addSeconds($qrCodeExpiryDuration)->toDateTimeString(),
                    ]);
                } else {
                    $qrCodeExpiryAt = $employee->getMeta('qr_code_expiry_at') ?? now()->toDateTimeString();

                    if (Carbon::parse($qrCodeExpiryAt)->isPast()) {
                        $employee->setMeta([
                            'qr_code_attendance' => Str::random(16),
                            'qr_code_expiry_at' => now()->addSeconds($qrCodeExpiryDuration)->toDateTimeString(),
                        ]);
                    }
                }

                $employee->save();

                $qrCodeText = $employee->getMeta('qr_code_attendance');
            }
        }

        $qrCode = (new QRCode)->render(
            $qrCodeText
        );

        return $qrCode;
    }

    public function markAttendance(Request $request)
    {
        $hasDynamicQrCode = config('config.employee.has_dynamic_qr_code');

        $employee = Employee::query()
            ->when($hasDynamicQrCode, function ($query) use ($request) {
                $query->where('meta->qr_code_attendance', $request->code);
            }, function ($query) use ($request) {
                $query->where('code_number', $request->code);
            })
            ->first();

        if ($employee) {
            if (! config('config.employee.enable_qr_code_attendance')) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }

            $this->markEmployeeAttendance($request, $employee);

            return;
        }

        $hasDynamicQrCode = config('config.employee.has_dynamic_qr_code');

        $student = Student::query()
            ->when($hasDynamicQrCode, function ($query) use ($request) {
                $query->where('meta->qr_code_attendance', $request->code);
            }, function ($query) use ($request) {
                $query->whereHas('admission', function ($q) use ($request) {
                    $q->where('code_number', $request->code);
                });
            })
            ->first();

        if ($student) {
            if (! config('config.student.enable_qr_code_attendance')) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }

            $this->markStudentAttendance($request, $student);

            return;
        }

        throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
    }

    public function markAnomymousAttendance(Request $request)
    {
        $token = $request->query('token');

        $device = Device::query()
            ->where('token', $token)
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Invalid token',
                'code' => 100,
            ], 422);
        }

        $this->markAttendance($request);
    }

    private function markStudentAttendance(Request $request, Student $student)
    {
        if (config('config.student.has_dynamic_qr_code')) {
            $qrCodeExpiryAt = $student->getMeta('qr_code_expiry_at') ?? now()->toDateTimeString();

            if (Carbon::parse($qrCodeExpiryAt)->isPast()) {
                throw ValidationException::withMessages(['message' => trans('student.attendance.qr_code_expired')]);
            }
        }

        $datetime = CalHelper::toDateTime(now()->toDateTimeString());
        $date = Carbon::parse($datetime)->format('Y-m-d');

        $params = [
            'ip' => $request->ip(),
        ];

        $existingAttendance = Attendance::query()
            ->where('batch_id', $student->batch_id)
            ->where('date', $date)
            ->where('session', AttendanceSession::FIRST)
            ->first();

        $data = [];
        if ($existingAttendance) {
            $data = $existingAttendance->values ?? [];
            $markedAttendance = false;
            foreach ($data as $value) {
                if (in_array($student->uuid, $value['uuids'])) {
                    $markedAttendance = true;
                }
            }

            if ($markedAttendance) {
                throw ValidationException::withMessages(['message' => trans('student.attendance.already_marked')]);
            }
        }

        $attendance = $existingAttendance ?? Attendance::forceCreate([
            'batch_id' => $student->batch_id,
            'date' => $date,
            'session' => AttendanceSession::FIRST,
            'is_default' => true,
        ]);

        $newData = [];

        if (empty($data)) {
            $newData[] = [
                'code' => 'P',
                'uuids' => [$student->uuid],
            ];
        } else {
            foreach ($data as $value) {
                if (! in_array($student->uuid, $value['uuids'] ?? [])) {
                    $value['uuids'][] = $student->uuid;
                }

                $newData[] = [
                    'code' => $value['code'] ?? 'P',
                    'uuids' => $value['uuids'],
                ];
            }
        }

        $attendance->values = $newData;
        $attendance->save();

        if (config('config.student.has_dynamic_qr_code')) {
            $qrCodeExpiryDuration = (int) config('config.student.qr_code_expiry_duration');

            $student->setMeta([
                'qr_code_attendance' => Str::random(16),
                'qr_code_expiry_at' => now()->addSeconds($qrCodeExpiryDuration)->toDateTimeString(),
            ]);

            $student->save();
        }
    }

    private function markEmployeeAttendance(Request $request, Employee $employee)
    {
        if (config('config.employee.has_dynamic_qr_code')) {
            $qrCodeExpiryAt = $employee->getMeta('qr_code_expiry_at') ?? now()->toDateTimeString();

            if (Carbon::parse($qrCodeExpiryAt)->isPast()) {
                throw ValidationException::withMessages(['message' => trans('employee.attendance.qr_code_expired')]);
            }
        }

        $datetime = CalHelper::toDateTime(now()->toDateTimeString());
        $date = Carbon::parse($datetime)->format('Y-m-d');

        $params = [
            'ip' => $request->ip(),
        ];

        $durationBetweenClockRequest = config('config.employee.duration_between_clock_request', 5);

        $lastTimesheet = Timesheet::query()
            ->whereEmployeeId($employee->id)
            ->where('date', today()->toDateString())
            ->where(function ($q) use ($durationBetweenClockRequest) {
                $q->where('in_at', '>=', now()->subMinutes($durationBetweenClockRequest)->toDateTimeString())
                    ->orWhere('out_at', '>=', now()->subMinutes($durationBetweenClockRequest)->toDateTimeString());
            })
            ->exists();

        if ($lastTimesheet) {
            throw ValidationException::withMessages(['message' => trans('employee.attendance.timesheet.recently_marked')]);
        }

        (new StoreTimesheet)->execute($employee, $datetime, $params);

        if (config('config.employee.has_dynamic_qr_code')) {
            $qrCodeExpiryDuration = (int) config('config.employee.qr_code_expiry_duration');

            $employee->setMeta([
                'qr_code_attendance' => Str::random(16),
                'qr_code_expiry_at' => now()->addSeconds($qrCodeExpiryDuration)->toDateTimeString(),
            ]);

            $employee->save();
        }
    }
}
