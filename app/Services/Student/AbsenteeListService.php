<?php

namespace App\Services\Student;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Contracts\ListGenerator;
use App\Enums\Student\AttendanceSession;
use App\Http\Resources\Student\AttendanceResource;
use App\Models\Academic\Batch;
use App\Models\Calendar\Holiday;
use App\Models\Student\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class AbsenteeListService extends ListGenerator
{
    public function getHeaders(Request $request): array
    {
        $headers = [
            [
                'key' => 'sno',
                'label' => '#',
                'print_label' => 'sno',
                'visibility' => true,
            ],
            [
                'key' => 'student',
                'label' => trans('student.student'),
                'print_label' => 'name',
                'visibility' => true,
            ],
        ];

        return $this->getDayWiseHeaders($request, $headers);
    }

    private function getDayWiseHeaders(Request $request, array $headers): array
    {
        $startDate = Carbon::parse($request->date)->startOfMonth();
        $endDate = Carbon::parse($request->date)->endOfMonth();

        while ($startDate->lte($endDate)) {
            $singleDay = $startDate->format('j');

            array_push($headers, [
                'key' => 'day_'.$singleDay,
                'label' => $singleDay,
                'print_label' => 'attendances._'.$singleDay.'.code',
                'visibility' => true,
            ]);

            $startDate->addDay();
        }

        if ($request->boolean('detail')) {
            array_push($headers, [
                'key' => 'cumulative_present',
                'label' => 'C',
                'print_label' => 'summary.cumulative',
                'visibility' => true,
            ]);

            array_push($headers, [
                'key' => 'monthly_present',
                'label' => 'P',
                'print_label' => 'summary.present',
                'visibility' => true,
            ]);

            array_push($headers, [
                'key' => 'total_present',
                'label' => 'T',
                'print_label' => 'summary.total',
                'visibility' => true,
            ]);

            array_push($headers, [
                'key' => 'present_percentage',
                'label' => '%',
                'print_label' => 'summary.present_percentage',
                'visibility' => true,
            ]);
        }

        return $headers;
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $batch = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');

        $request->merge([
            'on_date' => Carbon::parse($request->date)->startOfMonth()->toDateString(),
            'select_all' => true,
        ]);

        $students = (new FetchBatchWiseStudent)->execute($request->all());

        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $holidays = Holiday::query()
            ->byPeriod()
            ->betweenPeriod(
                Carbon::parse($request->date)->startOfMonth()->toDateString(),
                Carbon::parse($request->date)->endOfMonth()->toDateString()
            )
            ->get();

        $isHoliday = $holidays->filter(function ($holiday) use ($request) {
            return $holiday->start_date->value <= $request->date && $holiday->end_date->value >= $request->date;
        })->first();

        $attendancePastDayLimit = config('config.student.attendance_past_day_limit', 0);
        $pastDateLimit = today()->subDays($attendancePastDayLimit)->endOfDay();

        if ($request->boolean('detail')) {
            $overallAttendances = Attendance::query()
                ->whereBatchId($batch->id)
                ->whereSession(AttendanceSession::FIRST)
                ->where('date', '<=', Carbon::parse($request->date)->startOfMonth()->subMonth(1)->endOfMonth()->toDateString())
                ->where(function ($q) {
                    $q->whereNull('meta->is_holiday')
                        ->orWhere('meta->is_holiday', false);
                })
                ->get();

            $cumulativeAttendances = [];
            foreach ($overallAttendances as $overallAttendance) {
                $values = Arr::get($overallAttendance, 'values', []);
                foreach ($values as $value) {
                    foreach (Arr::get($value, 'uuids', []) as $uuid) {
                        // to check with dates
                        // $cumulativeAttendances[$uuid][] = Arr::get($value, 'code') . ' - ' . $overallAttendance->date->value;
                        $cumulativeAttendances[$uuid][] = Arr::get($value, 'code');
                    }
                }
            }

            $cumulativeWorkingDays = $overallAttendances->count();
        }

        $attendances = Attendance::query()
            ->whereBatchId($batch->id)
            ->whereSession(AttendanceSession::FIRST)
            ->whereBetween('date', [
                Carbon::parse($request->date)->startOfMonth()->toDateString(),
                Carbon::parse($request->date)->endOfMonth()->toDateString(),
            ])
            ->get();

        if ($request->boolean('detail')) {
            $monthlyWorkingDays = $attendances->filter(function ($attendance) {
                return ! $attendance->getMeta('is_holiday');
            })->count();

            $totalWorkingDays = $cumulativeWorkingDays + $monthlyWorkingDays;
        }

        $isMarked = false;

        $markedAttendance = $attendances
            ->where('date.value', $request->date)
            ->where('is_default', true)
            ->first();

        if ($attendances->where('date.value', $request->date)->count()) {
            $isMarked = true;
            // $isHoliday = null;
        }

        $isForceHoliday = $markedAttendance?->getMeta('is_holiday') ? true : false;
        $forceHolidayReason = $markedAttendance?->getMeta('holiday_reason');

        $attendances = $attendances
            ->groupBy('date.value')
            ->toArray();

        $currentMonthAttendances = [];
        $summary = [];
        foreach ($students as $index => $student) {
            $studentSummary = [];
            $startDate = Carbon::parse($request->date)->startOfMonth();
            $endDate = Carbon::parse($request->date)->endOfMonth();

            if ($request->boolean('detail')) {
                $cumulativeAttendance = $cumulativeAttendances[$student->uuid] ?? [];
                $cumulativePresent = collect($cumulativeAttendance)->filter(function ($value) {
                    return in_array($value, ['P', 'L', 'HD']);
                })->count();
            }

            $dateAttendanceCode = null;
            $items = [];
            while ($startDate->lte($endDate)) {
                $isAvailable = true;
                $isDisabled = false;
                $isActionable = false;
                $singleDay = $startDate->format('j');

                $recordStartDate = $student->start_date->carbon();
                $recordEndDate = $student->start_date->carbon();

                if ($student->leaving_date && $student->leaving_date < $startDate->toDateString()) {
                    $isDisabled = true;
                    $isAvailable = false;
                }

                if ($student->start_date->value > $startDate->toDateString()) {
                    $isDisabled = true;
                    $isAvailable = false;
                }

                $holiday = $holidays->filter(function ($holiday) use ($startDate) {
                    return $holiday->start_date->value <= $startDate->toDateString() && $holiday->end_date->value >= $startDate->toDateString();
                })->first();

                // let them forcefully mark attendance on holiday
                // if ($holiday) {
                //     $isDisabled = true;
                // }

                if ($startDate->toDateString() == $request->date) {
                    $isActionable = true;
                }

                if ($startDate->gt(today())) {
                    $isDisabled = true;
                }

                $attendance = Arr::first($attendances[$startDate->toDateString()] ?? []);

                if ($attendance) {
                    $holiday = null;
                }

                $attendanceCode = '';
                $holidayReason = '';
                $forceHoliday = (bool) Arr::get($attendance, 'meta.is_holiday');

                if ($forceHoliday) {
                    $holidayReason = Arr::get($attendance, 'meta.holiday_reason');
                } else {
                    $values = Arr::get($attendance, 'values', []);

                    $isToday = $startDate->isSameDay(today()->toDateString());
                    if ($index === 0) {
                        $currentMonthAttendances[] = [
                            'date' => $startDate->toDateString(),
                            'day' => $startDate->format('j'),
                            'is_holiday' => $holiday ? true : false,
                            'is_today' => $isToday ? true : false,
                            'has_attendance_marked' => count($values) ? true : false,
                        ];
                    }

                    foreach ($values as $value) {
                        if (in_array($student->uuid, Arr::get($value, 'uuids', []))) {
                            $attendanceCode = Arr::get($value, 'code');
                        }
                    }
                }

                if ($isDisabled || ! $isAvailable) {
                    $isActionable = false;
                    $attendanceCode = '';
                }

                if ($holiday || $forceHoliday) {
                    $attendanceCode = 'H';
                }

                if ($startDate->lt($pastDateLimit)) {
                    $isDisabled = true;
                }

                if ($startDate->toDateString() == $request->date) {
                    $dateAttendanceCode = $attendanceCode;
                }

                $summary[] = [
                    'date' => $startDate->toDateString(),
                    'student_id' => $student->id,
                    'code' => $attendanceCode,
                ];

                $items['_'.$singleDay] = [
                    'code' => $attendanceCode,
                    'color' => '',
                    'label' => '',
                    'is_holiday' => $holiday ? true : false,
                    'is_force_holiday' => $forceHoliday ? true : false,
                    'holiday_reason' => $holiday ? $holiday->name : $holidayReason,
                    'is_available' => $isAvailable,
                    'is_disabled' => $isDisabled,
                    'is_actionable' => $isActionable,
                ];

                $startDate->addDay();
            }

            $monthlyStudentPresent = collect($items)->filter(function ($value, $key) {
                return in_array($value['code'], ['P', 'L', 'HD']);
            })->count();

            if ($request->boolean('detail')) {
                $totalPresent = $cumulativePresent + $monthlyStudentPresent;

                $studentSummary = [
                    'cumulative' => $cumulativePresent,
                    'present' => $monthlyStudentPresent,
                    'total' => $totalPresent,
                    'present_percentage' => $totalWorkingDays ? (round(($totalPresent / $totalWorkingDays) * 100)) : 0,
                ];
            }

            $student->sno = $index + 1;
            $student->attendance = $dateAttendanceCode;
            $student->list_attendance = true;
            $student->summary = $studentSummary;
            $student->attendances = $items;
        }

        $isActionable = true;

        // Show the attendance button when holiday
        // if ($isHoliday) {
        //     $isActionable = false;
        // }

        if (Carbon::parse($request->date)->lt($pastDateLimit) || Carbon::parse($request->date)->gt(today())) {
            $isActionable = false;
        }

        $dateWiseSummary = collect($summary)
            ->groupBy('date')
            ->map(function ($dateData, $date) {
                $totalRecords = count($dateData);
                $presentCount = $dateData->whereIn('code', ['P', 'L', 'HD'])->count();
                $absentCount = $dateData->where('code', 'A')->count();
                $presentPercentage = ($presentCount / $totalRecords) * 100;

                return [
                    'date' => $date,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'present_percentage' => round($presentPercentage, 1),
                ];
            });

        return AttendanceResource::collection($students)
            ->additional([
                'headers' => $this->getHeaders($request),
                'meta' => [
                    'total' => $students->count(),
                    'is_actionable' => $isActionable,
                    'is_holiday' => $isHoliday ? true : false,
                    'is_force_holiday' => $isForceHoliday,
                    'holiday_reason' => $isHoliday?->name ?? $forceHolidayReason,
                    'is_marked' => $isMarked,
                    'month_name' => Carbon::parse($request->date)->format('F Y'),
                    'date_key' => '_'.date('j', strtotime($request->date)),
                    'current_month_attendances' => $currentMonthAttendances,
                    'date_wise_summary' => $dateWiseSummary,
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
