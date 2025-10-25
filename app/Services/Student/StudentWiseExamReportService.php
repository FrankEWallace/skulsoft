<?php

namespace App\Services\Student;

use App\Models\Academic\Period;
use App\Models\Academic\Subject;
use App\Models\Exam\Schedule;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StudentWiseExamReportService
{
    public function fetch(Request $request, Student $student)
    {
        $cacheKey = "student_exam_report_{$student->uuid}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($student) {
            $period = Period::query()
                ->findOrFail($student->period_id);

            $batch = $student->batch;

            $schedules = Schedule::query()
                ->select('exam_schedules.*', 'exam_terms.name as term_name', 'exams.name', 'exams.position as exam_position', 'exams.code as exam_code', 'exams.config as exam_config')
                ->join('exams', 'exams.id', '=', 'exam_schedules.exam_id')
                ->leftJoin('exam_terms', 'exams.term_id', '=', 'exam_terms.id')
                ->with('assessment')
                ->where('batch_id', $student->batch_id)
                ->orderBy('exams.position', 'asc')
                ->get();

            $schedules = $schedules->filter(function ($schedule) {
                if (is_null($schedule->exam_config)) {
                    return false;
                }

                $examConfig = json_decode($schedule->exam_config, true);

                return Arr::get($examConfig, $schedule->attempt->value.'_attempt.publish_marksheet', false);
            });

            $subjects = Subject::query()
                ->withSubjectRecord($batch->id, $batch->course_id)
                ->orderBy('subjects.position', 'asc')
                ->get();

            $subjectWiseStudents = SubjectWiseStudent::query()
                ->whereBatchId($batch->id)
                ->where('student_id', $student->id)
                ->get();

            $subjects = $subjects->filter(function ($subject) use ($subjectWiseStudents) {

                if (! $subject->is_elective) {
                    return true;
                } elseif ($subject->is_elective && $subjectWiseStudents->firstWhere('subject_id', $subject->id)) {
                    return true;
                }

                return false;
            })->sortBy('position');

            $examRecords = $this->getExamRecords($schedules, $subjects);

            $examRecords = collect($examRecords);

            $rows = [];
            $header = [];

            array_push($header, [
                'key' => 'subject_name',
                'label' => 'Subject',
            ]);

            foreach ($subjects as $subject) {
                array_push($header, [
                    'key' => 'subject_'.$subject->uuid,
                    'label' => $subject->name,
                ]);
            }

            foreach ($schedules as $schedule) {
                $row = [];

                array_push($row, [
                    'key' => 'exam_'.$schedule->uuid,
                    'label' => $schedule->name,
                ]);

                foreach ($subjects as $subject) {
                    $examRecord = $examRecords
                        ->where('schedule_id', $schedule->id)
                        ->where('subject_id', $subject->id)
                        ->first();

                    if (! $examRecord) {
                        array_push($row, [
                            'key' => 'subject_mark_'.$subject->uuid,
                            'label' => 'N/A',
                        ]);

                        continue;
                    }

                    $notApplicableStudents = $examRecord['not_applicable_students'] ?? [];

                    if (in_array($student->uuid, $notApplicableStudents)) {
                        array_push($row, [
                            'key' => 'subject_mark_'.$subject->uuid,
                            'label' => 'N/A',
                        ]);

                        continue;
                    }

                    $assessments = $examRecord['assessments'];
                    $marks = $examRecord['marks'];

                    $subjectMaxMarks = 0;
                    $subjectTotal = 0;

                    foreach ($assessments as $assessment) {
                        $assessmentMark = collect($marks)
                            ->firstWhere('code', $assessment['code']);

                        $obtainedMark = collect(Arr::get($assessmentMark, 'marks') ?? [])
                            ->firstWhere('uuid', $student->uuid)['obtained_mark'] ?? '';

                        $subjectMaxMarks += $assessment['max_mark'];
                        $subjectTotal += is_numeric($obtainedMark) ? $obtainedMark : 0;
                    }

                    array_push($row, [
                        'key' => 'subject_mark_'.$subject->uuid,
                        'label' => $subjectTotal.'/'.$subjectMaxMarks,
                    ]);
                }

                $rows[] = $row;
            }

            return [
                'header' => $header,
                'rows' => $rows,
            ];
        });
    }

    private function getExamRecords(Collection $schedules, Collection $subjects)
    {
        $examRecords = [];
        foreach ($schedules as $schedule) {
            $scheduleAssessmentRecords = collect($schedule->assessment->records ?? []);
            foreach ($schedule->records as $record) {

                $subject = $subjects->firstWhere('id', $record->subject_id);

                if (! $subject) {
                    continue;
                }

                $hasExam = $record->getConfig('has_exam');

                if (! $hasExam) {
                    continue;
                }

                $recordMarks = $record->marks;
                $recordAssessments = $record->getConfig('assessments', []);

                $assessments = [];
                foreach ($recordAssessments as $recordAssessment) {
                    $code = Arr::get($recordAssessment, 'code');

                    $assessmentMaxMark = Arr::get($recordAssessment, 'max_mark', 0);
                    $originalAssessmentMaxMark = $assessmentMaxMark;

                    $scheduleAssessmentRecord = $scheduleAssessmentRecords->firstWhere('code', $code);
                    $assessments[] = [
                        'code' => $code,
                        'name' => Arr::get($scheduleAssessmentRecord, 'name'),
                        'position' => Arr::get($scheduleAssessmentRecord, 'position', 0),
                        'max_mark' => $assessmentMaxMark,
                        'original_max_mark' => $originalAssessmentMaxMark,
                    ];
                }

                $assessments = collect($assessments)->sortBy('position')->values()->all();

                $examRecords[] = [
                    'exam_id' => $schedule->exam_id,
                    'schedule_id' => $schedule->id,
                    'subject_id' => $record->subject_id,
                    'has_grading' => $subject->has_grading,
                    'is_elective' => $subject->is_elective,
                    'assessments' => $assessments,
                    'not_applicable_students' => $record->getConfig('not_applicable_students', []),
                    'marks' => $recordMarks,
                ];
            }
        }

        return $examRecords;
    }
}
