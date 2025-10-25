<?php

namespace App\Services\Student\Report;

use App\Contracts\ListGenerator;
use App\Enums\Student\AttendanceSession;
use App\Models\Academic\Batch;
use App\Models\Calendar\Holiday;
use App\Models\Student\Attendance;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DateWiseAttendanceListService extends ListGenerator
{
    protected $allowedSorts = ['strength', 'present', 'absent', 'late', 'half_day', 'total'];

    protected $defaultSort = 'code_number';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
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
            [
                'key' => 'strength',
                'label' => trans('academic.student_strength'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'present',
                'label' => trans('student.attendance.types.present'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'absent',
                'label' => trans('student.attendance.types.absent'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'late',
                'label' => trans('student.attendance.types.late'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'halfDay',
                'label' => trans('student.attendance.types.half_day'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'total',
                'label' => trans('general.total'),
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): array
    {
        $batches = Str::toArray($request->query('batches'));

        $date = $request->query('date', today()->toDateString());
        $status = $request->query('status', 'all');

        $batches = Batch::query()
            ->select('batches.id', 'batches.uuid', 'courses.name as course_name', 'batches.name as batch_name')
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

        $attendances = Attendance::query()
            ->where('date', $date)
            ->whereIn('batch_id', $batches->pluck('id')->toArray())
            ->whereNull('subject_id')
            ->whereSession(AttendanceSession::FIRST)
            ->whereIsDefault(true)
            ->get();

        $holiday = Holiday::query()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        $students = Student::query()
            ->select('students.id', 'students.batch_id')
            ->with('admission')
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->whereIn('students.batch_id', $batches->pluck('id')->toArray())
            ->filterByStatus('studying')
            ->get();

        $grandTotalStrength = $students->count();
        $grandTotalPresent = 0;
        $grandTotalAbsent = 0;
        $grandTotalLate = 0;
        $grandTotalHalfDay = 0;
        $grandTotal = 0;
        $rows = [];
        foreach ($batches as $batch) {
            $incharges = $batch->incharges->pluck('employee.name')->toArray();
            $incharge = implode(', ', $incharges);

            $row['batch_uuid'] = $batch->uuid;
            $row['course_batch'] = $batch->course_name.' - '.$batch->batch_name;
            $row['incharge'] = $incharge;
            $row['strength'] = $students->where('batch_id', $batch->id)->count();
            $row['present'] = '-';
            $row['absent'] = '-';
            $row['late'] = '-';
            $row['half_day'] = '-';
            $row['total'] = '-';

            $attendance = $attendances->where('batch_id', $batch->id)->first();

            if (! $attendance && $holiday) {
                if ($status != 'marked') {
                    $rows[] = $row;
                }

                continue;
            }

            if (! $attendance) {
                if ($status != 'marked') {
                    $rows[] = $row;
                }

                continue;
            }

            if ($status == 'not_marked') {
                continue;
            }

            $values = collect($attendance->values);

            $present = count(Arr::get($values->firstWhere('code', 'P'), 'uuids', []));
            $absent = count(Arr::get($values->firstWhere('code', 'A'), 'uuids', []));
            $late = count(Arr::get($values->firstWhere('code', 'L'), 'uuids', []));
            $halfDay = count(Arr::get($values->firstWhere('code', 'HD'), 'uuids', []));

            $row['present'] = $present;
            $row['absent'] = $absent;
            $row['late'] = $late;
            $row['half_day'] = $halfDay;
            $row['total'] = $present + $absent + $late + $halfDay;

            $grandTotalPresent += $present;
            $grandTotalAbsent += $absent;
            $grandTotalLate += $late;
            $grandTotalHalfDay += $halfDay;
            $grandTotal += ($present + $absent + $late + $halfDay);

            $rows[] = $row;
        }

        $sortBy = $this->getSort();

        if ($request->query('sort')) {
            usort($rows, function ($a, $b) use ($sortBy) {
                return $a[$sortBy] <=> $b[$sortBy];
            });

            if ($request->query('order') === 'desc') {
                $rows = array_reverse($rows);
            }
        }

        return [
            'headers' => $this->getHeaders(),
            'data' => $rows,
            'meta' => [
                'filename' => 'Date Wise Attendance Report',
                'total' => $batches->count(),
                'has_footer' => true,
            ],
            'footers' => [
                ['key' => 'course_batch', 'label' => trans('general.total')],
                ['key' => 'strength', 'label' => $grandTotalStrength],
                ['key' => 'present', 'label' => $grandTotalPresent],
                ['key' => 'absent', 'label' => $grandTotalAbsent],
                ['key' => 'late', 'label' => $grandTotalLate],
                ['key' => 'half_day', 'label' => $grandTotalHalfDay],
                ['key' => 'total', 'label' => $grandTotal],
            ],
        ];
    }

    public function list(Request $request): array
    {
        return $this->filter($request);
    }
}
