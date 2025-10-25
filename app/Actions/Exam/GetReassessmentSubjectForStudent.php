<?php

namespace App\Actions\Exam;

use App\Enums\Exam\AssessmentAttempt;
use App\Enums\Exam\Result;
use App\Models\Academic\SubjectRecord;
use App\Models\Exam\Result as ExamResult;
use App\Models\Exam\Schedule;
use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class GetReassessmentSubjectForStudent
{
    public function execute(Student $student, Schedule $schedule)
    {
        if (! $schedule->is_reassessment) {
            return [];
        }

        $attemptNumber = AssessmentAttempt::getAttemptNumber($schedule->attempt->value);
        $previousAttempt = AssessmentAttempt::getAttempt($attemptNumber - 1);

        $previousSchedule = Schedule::query()
            ->where('exam_id', $schedule->exam_id)
            ->where('batch_id', $schedule->batch_id)
            ->where('attempt', $previousAttempt)
            ->where('id', '!=', $schedule->id)
            ->first();

        $reassessmentSubjects = [];

        $examResult = ExamResult::query()
            ->where('student_id', $student->id)
            ->where('exam_id', $previousSchedule->exam_id)
            ->where('attempt', $previousSchedule->attempt)
            ->first();

        if (! $examResult) {
            return throw ValidationException::withMessages(['message' => trans('exam.schedule.could_not_find_result')]);
        }

        $reassessmentSubjectCodes = [];
        if ($examResult->result == Result::REASSESSMENT) {
            $reassessmentSubjectCodes = Arr::get($examResult->subjects, 'reassessment', []);
        }

        $batch = $schedule->batch;

        $subjectRecords = SubjectRecord::query()
            ->where(function ($q) use ($batch) {
                $q->where('course_id', $batch->course_id)
                    ->orWhere('batch_id', $batch->id);
            })
            ->whereIn('subject_id', $schedule->records->pluck('subject_id'))
            ->get();

        foreach ($reassessmentSubjectCodes as $subjectCode) {
            $record = $schedule->records->firstWhere('subject.code', $subjectCode);
            $subjectRecord = $subjectRecords->firstWhere('subject_id', $record->subject_id);

            $reassessmentSubjects[] = [
                'uuid' => $record->uuid,
                'subject' => [
                    'name' => $record->subject->name,
                    'code' => $record->subject->code,
                    'description' => $record->subject->description,
                ],
                'has_exam' => (bool) $record->getConfig('has_exam'),
                'has_grading' => (bool) $subjectRecord->has_grading,
                'exam_fee' => $subjectRecord->exam_fee,
                'sort_date' => $record->date->value,
                'date' => $record->date,
                'start_time' => $record->start_time,
                'duration' => $record->duration,
                'end_time' => $record->end_time,
            ];
        }

        return $reassessmentSubjects;
    }
}
