<?php

namespace App\Services\Employee\Leave;

use App\Enums\Employee\Leave\RequestStatus as LeaveRequestStatus;
use App\Helpers\CalHelper;
use App\Models\Employee\Attendance\Attendance;
use App\Models\Employee\Leave\Allocation as LeaveAllocation;
use App\Models\Employee\Leave\AllocationRecord as LeaveAllocationRecord;
use App\Models\Employee\Leave\Request as LeaveRequest;
use App\Models\Employee\Leave\RequestRecord as LeaveRequestRecord;
use App\Models\Employee\Payroll\Payroll;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestActionService
{
    public function undoStatus(Request $request, LeaveRequest $leaveRequest)
    {
        $payrollGenerated = Payroll::query()
            ->whereEmployeeId($leaveRequest->employee_id)
            ->betweenPeriod($leaveRequest->start_date->value, $leaveRequest->end_date->value)
            ->exists();

        if ($payrollGenerated) {
            throw ValidationException::withMessages(['message' => trans('employee.leave.request.could_not_perform_if_payroll_generated')]);
        }

        if ($leaveRequest->status == LeaveRequestStatus::REQUESTED) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        \DB::beginTransaction();

        if (in_array($leaveRequest->status, [LeaveRequestStatus::REJECTED])) {
            $leaveRequest->status = LeaveRequestStatus::REQUESTED;
            $leaveRequest->save();

            LeaveRequestRecord::query()
                ->whereLeaveRequestId($leaveRequest->id)
                ->delete();

            $date = Carbon::parse($leaveRequest->start_date->value);
            $endDate = Carbon::parse($leaveRequest->end_date->value);

            while ($date <= $endDate) {
                Attendance::query()
                    ->whereEmployeeId($leaveRequest->model_id)
                    ->where('attendance_symbol', 'LWP')
                    ->where('meta->is_forced_attendance', true)
                    ->delete();

                $date->addDay(1);
            }

        } elseif (in_array($leaveRequest->status, [LeaveRequestStatus::APPROVED, LeaveRequestStatus::PARTIALLY_APPROVED])) {
            $duration = $leaveRequest->status != LeaveRequestStatus::PARTIALLY_APPROVED ? CalHelper::dateDiff($leaveRequest->start_date->value, $leaveRequest->end_date->value) : count($leaveRequest->getMeta('dates', []));

            $leaveAllocation = LeaveAllocation::query()
                ->whereEmployeeId($leaveRequest->model_id)
                ->where('start_date', '<=', $leaveRequest->start_date->value)
                ->where('end_date', '>=', $leaveRequest->end_date->value)
                ->first();

            $request->merge([
                'duration' => $duration,
                'leave_allocation_id' => $leaveAllocation?->id,
            ]);
            $this->updateLeaveBalance($request, $leaveRequest, 'decrement');

            $this->deleteAttendance($leaveRequest);

            $leaveRequest->status = LeaveRequestStatus::REQUESTED;
            $leaveRequest->save();

            LeaveRequestRecord::query()
                ->whereLeaveRequestId($leaveRequest->id)
                ->delete();
        }

        \DB::commit();
    }

    public function updateStatus(Request $request, LeaveRequest $leaveRequest)
    {
        \DB::beginTransaction();

        $leaveRequestRecord = LeaveRequestRecord::firstOrCreate([
            'leave_request_id' => $leaveRequest->id,
        ]);

        $leaveRequestRecord->approve_user_id = auth()->id();
        $leaveRequestRecord->status = $request->status;
        $leaveRequestRecord->comment = $request->comment;
        $leaveRequestRecord->save();

        $leaveRequest->status = $request->status;

        if ($request->balance == 0 && config('config.employee.allow_employee_request_leave_with_exhausted_credit')) {
            $leaveRequest->setMeta(['leave_with_exhausted_credit' => true]);
        }

        if ($request->status == LeaveRequestStatus::PARTIALLY_APPROVED->value) {
            $leaveRequest->setMeta(['dates' => $request->dates]);
        }

        $leaveRequest->save();

        $this->updateLeaveBalance($request, $leaveRequest);

        $this->updateAttendance($leaveRequest);

        \DB::commit();
    }

    private function updateLeaveBalance(Request $request, LeaveRequest $leaveRequest, $action = 'increment'): void
    {
        if ($leaveRequest->getMeta('leave_with_exhausted_credit')) {
            return;
        }

        if (! in_array($leaveRequest->status, [LeaveRequestStatus::APPROVED, LeaveRequestStatus::PARTIALLY_APPROVED])) {
            return;
        }

        if ($leaveRequest->is_half_day) {
            $request->merge(['duration' => 0.5]);
        }

        LeaveAllocationRecord::query()
            ->whereLeaveAllocationId($request->leave_allocation_id)
            ->whereLeaveTypeId($leaveRequest->leave_type_id)
            ->$action('used', $request->duration);
    }

    private function updateAttendance(LeaveRequest $leaveRequest): void
    {
        $attendanceCode = $leaveRequest->getMeta('leave_with_exhausted_credit') ? 'LWP' : 'L';

        if ($attendanceCode == 'L' && $leaveRequest->is_half_day) {
            $attendanceCode = 'HDL';
        } elseif ($attendanceCode == 'LWP' && $leaveRequest->is_half_day) {
            $attendanceCode = 'HD';
        }

        $dates = CalHelper::datesInPeriod($leaveRequest->start_date->value, $leaveRequest->end_date->value);

        if ($leaveRequest->status == LeaveRequestStatus::REJECTED && ! $leaveRequest->is_half_day) {
            foreach ($dates as $date) {
                $attendance = Attendance::query()
                    ->firstOrCreate([
                        'date' => $date,
                        'employee_id' => $leaveRequest->model_id,
                    ]);

                $attendance->attendance_type_id = null;
                $attendance->attendance_symbol = 'LWP';
                $attendance->setMeta(['is_forced_attendance' => true]);
                $attendance->save();
            }

            return;
        }

        if (! in_array($leaveRequest->status, [LeaveRequestStatus::PARTIALLY_APPROVED, LeaveRequestStatus::APPROVED])) {
            Attendance::whereIn('date', $dates)->whereEmployeeId($leaveRequest->model_id)->whereAttendanceSymbol($attendanceCode)->delete();

            return;
        }

        Attendance::whereIn('date', $dates)->whereEmployeeId($leaveRequest->model_id)->whereNull('attendance_symbol')->delete();

        $partiallyApprovedDates = collect($leaveRequest->getMeta('dates', []))->map(function ($date) {
            return trim($date);
        })->toArray();

        $attendances = [];
        foreach ($dates as $date) {
            $newAttendanceCode = $attendanceCode;
            if ($leaveRequest->status == LeaveRequestStatus::PARTIALLY_APPROVED && ! in_array($date, $partiallyApprovedDates)) {
                $newAttendanceCode = 'LWP';
            }

            $attendances[] = ['date' => $date, 'employee_id' => $leaveRequest->model_id, 'attendance_symbol' => $newAttendanceCode, 'attendance_type_id' => null, 'uuid' => (string) Str::uuid()];
        }

        Attendance::upsert(
            $attendances,
            ['date', 'employee_id'],
            ['attendance_symbol', 'attendance_type_id', 'uuid']
        );
    }

    private function deleteAttendance(LeaveRequest $leaveRequest): void
    {
        $dates = CalHelper::datesInPeriod($leaveRequest->start_date->value, $leaveRequest->end_date->value);

        Attendance::whereIn('date', $dates)->whereEmployeeId($leaveRequest->model_id)->whereIn('attendance_symbol', ['L', 'LWP', 'HDL'])->delete();
    }
}
