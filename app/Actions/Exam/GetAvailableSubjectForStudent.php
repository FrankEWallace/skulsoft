<?php

namespace App\Actions\Exam;

use App\Models\Academic\SubjectRecord;
use App\Models\Exam\Schedule;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;

class GetAvailableSubjectForStudent
{
    public function execute(Student $student, Schedule $schedule)
    {
        if ($schedule->is_reassessment) {
            return [];
        }

        $availableSubjects = [];

        $batch = $schedule->batch;

        $subjectRecords = SubjectRecord::query()
            ->where(function ($q) use ($batch) {
                $q->where('course_id', $batch->course_id)
                    ->orWhere('batch_id', $batch->id);
            })
            ->whereIn('subject_id', $schedule->records->pluck('subject_id'))
            ->get();

        $subjectWiseStudents = SubjectWiseStudent::query()
            ->whereBatchId($batch->id)
            ->whereStudentId($student->id)
            ->get();

        foreach ($schedule->records as $record) {
            $subjectRecord = $subjectRecords->firstWhere('subject_id', $record->subject_id);

            if ($subjectRecord->is_elective && ! $subjectWiseStudents->firstWhere('subject_id', $record->subject_id)) {
                continue;
            }

            $availableSubjects[] = [
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

        return $availableSubjects;
    }
}
