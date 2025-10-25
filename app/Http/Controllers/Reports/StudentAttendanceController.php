<?php

namespace App\Http\Controllers\Reports;

use App\Enums\Student\AttendanceSession;
use App\Helpers\CalHelper;
use App\Models\Academic\Batch;
use App\Models\Academic\Period;
use App\Models\Calendar\Holiday;
use App\Models\Incharge;
use App\Models\Student\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StudentAttendanceController
{
    public function __invoke(Request $request)
    {
        $period = Period::query()
            ->findOrFail(auth()->user()->current_period_id);

        $startDate = $period->start_date->value;
        $endDate = $period->end_date->value;

        $totalDays = CalHelper::dateDiff($startDate, today()->subDay(1)->toDateString());

        $holidays = Holiday::query()
            ->betweenPeriod($startDate, today()->subDay(1)->toDateString())
            ->get();

        $holidayCount = 0;

        foreach ($holidays as $holiday) {
            $startDate = $holiday->start_date->value;
            $endDate = $holiday->end_date->value;

            $holidayCount += CalHelper::dateDiff($startDate, $endDate);
        }

        $workingDays = $totalDays - $holidayCount;

        $attendances = Attendance::query()
            ->select('batches.name as batch_name', 'batches.id as batch_id', 'courses.name as course_name', \DB::raw('COUNT(DISTINCT date) as attendance_count'))
            ->join('batches', 'batches.id', '=', 'student_attendances.batch_id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->whereBetween('date', [
                $period->start_date->value,
                today()->subDay(1)->toDateString(),
            ])
            ->whereNull('subject_id')
            ->where('session', AttendanceSession::FIRST)
            ->groupBy('batch_id')
            ->when($request->query('order') == 'attendance', function ($q) {
                $q->orderBy('attendance_count');
            }, function ($q) {
                $q->orderBy('courses.position', 'asc')
                    ->orderBy('batches.position', 'asc');
            })
            ->get();

        $incharges = Incharge::query()
            ->whereHasMorph(
                'model',
                [Batch::class],
                function (Builder $query) {
                    $query->whereNotNull('id');
                }
            )
            ->with(['employee' => fn ($q) => $q->summary()])
            ->get();

        $data = [];

        foreach ($attendances as $attendance) {
            $batchIncharge = $incharges
                ->where('model_id', $attendance->batch_id)
                ->first();

            $data[] = [
                'batch' => $attendance->batch_name.' '.$attendance->course_name,
                'count' => $attendance->attendance_count,
                'incharge' => $batchIncharge?->employee?->name,
            ];
        }

        return view('reports.student.attendance', compact('totalDays', 'holidayCount', 'workingDays', 'data', 'incharges'));
    }
}
