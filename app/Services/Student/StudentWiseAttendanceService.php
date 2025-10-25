<?php

namespace App\Services\Student;

use App\Models\Academic\Period;
use App\Models\Calendar\Holiday;
use App\Models\Student\Attendance;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StudentWiseAttendanceService
{
    public function fetch(Request $request, Student $student)
    {
        $cacheKey = "student_attendance_{$student->uuid}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($request, $student) {
            $period = Period::query()
                ->findOrFail($student->period_id);

            $holidays = Holiday::query()
                ->where('start_date', '>=', $period->start_date->value)
                ->where('end_date', '<=', $period->end_date->value)
                ->get();

            $attendances = Attendance::query()
                ->whereBatchId($student->batch_id)
                ->orderBy('date', 'asc')
                ->get();

            $startDate = Carbon::parse($period->start_date->value)->startOfMonth();
            $endDate = Carbon::parse($period->end_date->value)->endOfMonth();

            $months = [];
            $summary = [];
            while ($startDate->lte($endDate)) {
                $months[] = $startDate->format('M Y');
                $summary[$startDate->format('M Y')] = [
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'half_day' => 0,
                    'holiday' => 0,
                ];
                $startDate->addMonth();
            }

            $rows = [];
            $header = [];

            array_push($header, [
                'key' => 'date',
                'label' => trans('general.date'),
            ]);

            for ($i = 1; $i <= 31; $i++) {
                $row = [];

                array_push($row, [
                    'key' => 'day_'.$i,
                    'name' => trans('general.date'),
                    'label' => $i,
                ]);

                foreach ($months as $month) {
                    if ($i == 1) {
                        $header[] = [
                            'key' => $month,
                            'label' => $month,
                        ];
                    }

                    $date = Carbon::parse($month)->startOfMonth()->addDays($i - 1);

                    if ($date->format('M Y') != $month) {
                        $row[] = [
                            'key' => $date->toDateString(),
                            'name' => trans('general.date'),
                            'label' => '',
                        ];
                    } else {
                        $attendance = $this->getAttendanceCode($student, $holidays, $attendances, $date->toDateString());

                        if ($attendance == 'P') {
                            $summary[$date->format('M Y')]['present']++;
                        } elseif ($attendance == 'A') {
                            $summary[$date->format('M Y')]['absent']++;
                        } elseif ($attendance == 'L') {
                            $summary[$date->format('M Y')]['late']++;
                        } elseif ($attendance == 'HD') {
                            $summary[$date->format('M Y')]['half_day']++;
                        } elseif ($attendance == 'H') {
                            $summary[$date->format('M Y')]['holiday']++;
                        }

                        $icon = '';
                        $color = '';
                        if ($attendance == 'H') {
                            $icon = 'fas fa-circle-h';
                            $color = 'text-primary dark:text-gray-400';
                        } elseif ($attendance == 'P') {
                            $icon = 'far fa-check-circle';
                            $color = 'text-success';
                        } elseif ($attendance == 'A') {
                            $icon = 'far fa-times-circle';
                            $color = 'text-danger';
                        } elseif ($attendance == 'L') {
                            $icon = 'fas fa-coffee';
                            $color = 'text-warning';
                        } elseif ($attendance == 'HD') {
                            $icon = 'fas fa-history';
                            $color = 'text-info';
                        }

                        $row[] = [
                            'key' => $date->toDateString(),
                            'name' => $month,
                            'label' => $attendance,
                            'icon' => $icon,
                            'color' => $color,
                        ];
                    }
                }

                $rows[] = [
                    'key' => 'day_'.$i,
                    'row' => $row,
                ];
            }

            $presentRow[] = [
                'key' => 'present',
                'label' => 'P',
            ];
            $absentRow[] = [
                'key' => 'absent',
                'label' => 'A',
            ];
            $lateRow[] = [
                'key' => 'late',
                'label' => 'L',
            ];
            $halfDayRow[] = [
                'key' => 'half_day',
                'label' => 'HD',
            ];
            $holidayRow[] = [
                'key' => 'holiday',
                'label' => 'H',
            ];
            foreach ($months as $month) {
                $presentRow[] = [
                    'key' => $month,
                    'label' => $summary[$month]['present'],
                    'count' => (int) $summary[$month]['present'],
                ];
                $absentRow[] = [
                    'key' => $month,
                    'label' => $summary[$month]['absent'],
                    'count' => (int) $summary[$month]['absent'],
                ];
                $lateRow[] = [
                    'key' => $month,
                    'label' => $summary[$month]['late'],
                    'count' => (int) $summary[$month]['late'],
                ];
                $halfDayRow[] = [
                    'key' => $month,
                    'label' => $summary[$month]['half_day'],
                    'count' => (int) $summary[$month]['half_day'],
                ];
                $holidayRow[] = [
                    'key' => $month,
                    'label' => $summary[$month]['holiday'],
                    'count' => (int) $summary[$month]['holiday'],
                ];
            }

            $total = [
                'working_days' => [
                    'key' => 'working_days',
                    'label' => 'WD',
                    'description' => trans('student.attendance.types.working_days'),
                    'count' => $attendances->count(),
                    'design' => 'secondary',
                ],
                'holiday' => [
                    'key' => 'holiday',
                    'label' => 'H',
                    'description' => trans('student.attendance.types.holiday'),
                    'count' => collect($holidayRow)->sum('count'),
                    'design' => 'primary',
                ],
                'present' => [
                    'key' => 'present',
                    'label' => 'P',
                    'description' => trans('student.attendance.types.present'),
                    'count' => collect($presentRow)->sum('count'),
                    'design' => 'success',
                ],
                'absent' => [
                    'key' => 'absent',
                    'label' => 'A',
                    'description' => trans('student.attendance.types.absent'),
                    'count' => collect($absentRow)->sum('count'),
                    'design' => 'danger',
                ],
                'late' => [
                    'key' => 'late',
                    'label' => 'L',
                    'description' => trans('student.attendance.types.late'),
                    'count' => collect($lateRow)->sum('count'),
                    'design' => 'warning',
                ],
                'half_day' => [
                    'key' => 'half_day',
                    'label' => 'HD',
                    'description' => trans('student.attendance.types.half_day'),
                    'count' => collect($halfDayRow)->sum('count'),
                    'design' => 'info',
                ],
            ];

            $rows[] = [
                'type' => 'footer',
                'key' => 'present',
                'row' => $presentRow,
            ];
            $rows[] = [
                'type' => 'footer',
                'key' => 'absent',
                'row' => $absentRow,
            ];
            $rows[] = [
                'type' => 'footer',
                'key' => 'late',
                'row' => $lateRow,
            ];
            $rows[] = [
                'type' => 'footer',
                'key' => 'half_day',
                'row' => $halfDayRow,
            ];
            $rows[] = [
                'type' => 'footer',
                'key' => 'holiday',
                'row' => $holidayRow,
            ];

            $chartData = [];
            $labels = $months;

            $chartData['labels'] = $labels;
            $chartData['datasets'] = [
                [
                    'label' => trans('student.attendance.types.present'),
                    'data' => collect($presentRow)->pluck('count'),
                    'backgroundColor' => '#28a745',
                    'borderColor' => '#28a745',
                ],
                [
                    'label' => trans('student.attendance.types.absent'),
                    'data' => collect($absentRow)->pluck('count'),
                    'backgroundColor' => '#dc3545',
                    'borderColor' => '#dc3545',
                ],
                [
                    'label' => trans('student.attendance.types.late'),
                    'data' => collect($lateRow)->pluck('count'),
                    'backgroundColor' => '#ffa500',
                    'borderColor' => '#ffa500',
                ],
                [
                    'label' => trans('student.attendance.types.half_day'),
                    'data' => collect($halfDayRow)->pluck('count'),
                    'backgroundColor' => '#007bff',
                    'borderColor' => '#007bff',
                ],
                [
                    'label' => trans('student.attendance.types.holiday'),
                    'data' => collect($holidayRow)->pluck('count'),
                    'backgroundColor' => '#330F57',
                    'borderColor' => '#330F57',
                ],
            ];

            return compact('rows', 'header', 'total', 'chartData');
        });
    }

    private function getAttendanceCode(Student $student, Collection $holidays, Collection $attendances, string $date)
    {
        $holiday = $holidays->filter(function ($holiday) use ($date) {
            return $holiday->start_date->value <= $date && $holiday->end_date->value >= $date;
        })->first();

        $attendance = $attendances->firstWhere('date.value', $date);

        if ($student->leaving_date && $student->leaving_date < $date) {
            // left
            return '';
        } elseif ($student->start_date->value > $date) {
            // not_started
            return '';
        } elseif (! $attendance && $holiday) {
            return 'H';
        } elseif ($attendance) {
            if (Arr::get($attendance, 'meta.is_holiday')) {
                return 'H';
            } else {
                $values = Arr::get($attendance, 'values', []);

                $attendanceCode = null;
                foreach ($values as $value) {
                    if (in_array($student->uuid, Arr::get($value, 'uuids', []))) {
                        $attendanceCode = Arr::get($value, 'code');
                    }
                }

                return $attendanceCode;
            }
        }

        return '';
    }
}
