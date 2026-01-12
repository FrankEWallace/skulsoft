<?php

namespace App\Services\Student\Report;

use App\Contracts\ListGenerator;
use App\Enums\Student\AttendanceSession;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Period;
use App\Models\Calendar\Holiday;
use App\Models\Student\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BatchWiseAttendanceListService extends ListGenerator
{
    protected $allowedSorts = [];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(array $months): array
    {
        $headers = [
            [
                'key' => 'course_batch',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_batch',
                'print_sub_label' => 'incharge',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        foreach ($months as $index => $month) {
            $key = 'month_'.($index + 1);
            $headers[] = [
                'key' => $key,
                'label' => Carbon::parse($month['start_date'])->format('M Y'),
                'print_label' => 'attendances.'.$key,
                'sortable' => false,
                'visibility' => true,
            ];
        }

        $headers[] = [
            'key' => 'total',
            'label' => trans('general.total'),
            'sortable' => false,
            'visibility' => true,
        ];

        return $headers;
    }

    public function filter(Request $request): array
    {
        $batches = Str::toArray($request->query('batches'));

        $period = Period::query()
            ->findOrFail(auth()->user()->current_period_id);

        $months = [];

        $periodStartDate = Carbon::parse($period->start_date->value);
        $periodEndDate = Carbon::parse($period->end_date->value);

        while ($periodStartDate->lte($periodEndDate)) {
            $months[] = [
                'start_date' => $periodStartDate->copy()->startOfMonth()->toDateString(),
                'end_date' => $periodStartDate->copy()->endOfMonth()->toDateString(),
                'working_days' => 0,
                'holidays' => 0,
            ];
            $periodStartDate->addMonth();
        }

        $batches = Batch::query()
            ->select('batches.id', 'courses.name as course_name', 'batches.name as batch_name')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->byPeriod()
            ->filterAccessible()
            ->withCurrentIncharges()
            ->when($batches, function ($q) use ($batches) {
                $q->whereIn('batches.uuid', $batches);
            })
            ->orderBy('courses.position', 'asc')
            ->orderBy('batches.position', 'asc')
            ->get();

        $holidays = Holiday::query()
            ->betweenPeriod($periodStartDate->toDateString(), $periodEndDate->toDateString())
            ->get();

        $rows = [];
        $data = [];
        foreach ($months as $index => $month) {
            $attendances = Attendance::query()
                ->select('batch_id', \DB::raw('COUNT(DISTINCT date) as attendance_count'))
                ->whereNull('subject_id')
                ->whereSession(AttendanceSession::FIRST)
                ->whereIsDefault(true)
                ->whereIn('batch_id', $batches->pluck('id')->toArray())
                ->where(function ($q) {
                    $q->whereNull('student_attendances.meta->is_holiday')
                        ->orWhere('student_attendances.meta->is_holiday', false);
                })
                ->whereBetween('date', [
                    Arr::get($month, 'start_date'),
                    Arr::get($month, 'end_date'),
                ])
                ->groupBy('batch_id')
                ->get();

            $monthYear = Carbon::parse(Arr::get($month, 'start_date'))->format('M Y');

            foreach ($batches as $batch) {
                $attendance = $attendances->where('batch_id', $batch->id)->first();

                $data[$monthYear][] = [
                    'batch' => $batch->course_name.' - '.$batch->batch_name,
                    'batch_id' => $batch->id,
                    'attendance_count' => $attendance?->attendance_count ?? 0,
                ];
            }
        }

        foreach ($batches as $batch) {
            $monthlyAttendances = [];
            $total = 0;
            foreach ($months as $index => $month) {
                $monthYear = Carbon::parse(Arr::get($month, 'start_date'))->format('M Y');

                $attendance = collect(Arr::get($data, $monthYear))
                    ->where('batch_id', $batch->id)
                    ->first();

                $monthlyAttendances['month_'.($index + 1)] = Arr::get($attendance, 'attendance_count', 0);

                $total += Arr::get($attendance, 'attendance_count', 0);
            }

            $incharges = $batch->incharges->pluck('employee.name')->toArray();
            $incharge = implode(', ', $incharges);

            $rows[] = [
                'course_batch' => $batch->course_name.' - '.$batch->batch_name,
                'incharge' => $incharge ?? '-',
                'attendances' => $monthlyAttendances,
                'total' => $total,
            ];
        }

        return [
            'headers' => $this->getHeaders($months),
            'data' => $rows,
            'meta' => [
                'filename' => 'Batch Wise Attendance Report',
                'total' => $batches->count(),
            ],
        ];
    }

    public function list(Request $request): array
    {
        return $this->filter($request);
    }
}
