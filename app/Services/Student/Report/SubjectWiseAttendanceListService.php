<?php

namespace App\Services\Student\Report;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Contracts\ListGenerator;
use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Student\Attendance;
use App\Models\Student\SubjectWiseStudent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SubjectWiseAttendanceListService extends ListGenerator
{
    protected $allowedSorts = [];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(Collection $subjects): array
    {
        $headers = [
            [
                'key' => 'student',
                'label' => trans('student.student'),
                'print_label' => 'name',
                'print_sub_label' => 'code_number',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        foreach ($subjects as $index => $subject) {
            $key = 'subject_'.($index + 1);
            $headers[] = [
                'key' => $key,
                'label' => $subject->code.'('.$subject->marked_attendance.')',
                'print_label' => 'subjects.'.$key.'.present',
                'print_sub_label' => 'subjects.'.$key.'.percentage',
                'sortable' => false,
                'visibility' => true,
            ];
        }

        return $headers;
    }

    public function filter(Request $request): array
    {
        $request->validate([
            'batch' => 'required|uuid',
            'date' => 'required',
        ]);

        $batch = Batch::query()
            ->with('course')
            ->findByUuidOrFail($request->query('batch'));

        $month = $request->query('date');

        $startDate = Carbon::parse($month)->startOfMonth()->toDateString();
        $endDate = Carbon::parse($month)->endOfMonth()->toDateString();

        $attendances = Attendance::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->where('batch_id', $batch->id)
            ->whereNotNull('subject_id')
            ->get();

        $subjects = Subject::query()
            ->withSubjectRecord($batch->id, $batch->course_id)
            ->orderBy('subjects.position', 'asc')
            ->get();

        $params = $request->all();
        $params['for_subject'] = true;

        $students = (new FetchBatchWiseStudent)->execute($params);

        $subjectWiseStudents = SubjectWiseStudent::query()
            ->whereBatchId($batch->id)
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get();

        $subjectAttendances = [];
        foreach ($subjects as $subject) {
            foreach ($students as $student) {
                $subjectAttendances[$subject->id][$student->id] = 0;
            }
        }

        $subjectAttendances = [];

        foreach ($subjects as $subject) {
            $subjectAttendances[$subject->id] = $attendances->where('subject_id', $subject->id);
        }

        $rows = [];

        foreach ($students as $studentIndex => $student) {
            $total = 0;

            $row = [
                'name' => $student->name,
                'code_number' => $student->code_number,
            ];

            $subjectRows = [];
            foreach ($subjects as $index => $subject) {

                if ($subject->is_elective && ! $subjectWiseStudents->where('student_id', $student->id)->firstWhere('subject_id', $subject->id)) {
                    $subjectRows['subject_'.($index + 1)] = [
                        'present' => '-',
                        'percentage' => '',
                    ];

                    continue;
                }

                $subjectWiseAbsentCount = 0;
                $subjectWisePresentCount = 0;

                $subjectWiseAttendances = $subjectAttendances[$subject->id] ?? [];

                foreach ($subjectWiseAttendances as $subjectWiseAttendance) {

                    $isPresent = collect($subjectWiseAttendance->values)
                        ->filter(function ($item) {
                            // return $item['code'] === 'A';
                            return in_array($item['code'], ['P', 'L', 'HD']);
                        })
                        ->flatMap(function ($item) {
                            return $item['uuids'];
                        })
                        ->contains($student->uuid) ? true : false;

                    if ($isPresent) {
                        $subjectWisePresentCount++;
                    }
                }

                // $presentCount = $subjectWiseAttendances->count() - $subjectWiseAbsentCount;
                $presentCount = $subjectWisePresentCount;

                $subjectRows['subject_'.($index + 1)] = [
                    'present' => $presentCount,
                    'percentage' => $subjectWiseAttendances->count() ? (round(($presentCount / $subjectWiseAttendances->count()) * 100, 2).'%') : 0,
                ];

                if ($studentIndex === 0) {
                    $subject->marked_attendance = $subjectWiseAttendances->count();
                }
            }

            $row['subjects'] = $subjectRows;
            $rows[] = $row;
        }

        return [
            'headers' => $this->getHeaders($subjects),
            'data' => $rows,
            'meta' => [
                'filename' => 'Subject Wise Attendance Report',
                'total' => $students->count(),
            ],
        ];
    }

    public function list(Request $request): array
    {
        return $this->filter($request);
    }
}
