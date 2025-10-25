<?php

namespace App\Services\Student;

use App\Models\Academic\Period;
use App\Models\Academic\Subject;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StudentWiseSubjectService
{
    public function fetch(Request $request, Student $student)
    {
        $cacheKey = "student_subject_{$student->uuid}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($request, $student) {
            $period = Period::query()
                ->findOrFail($student->period_id);

            $batch = $student->batch;

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
            })
                ->map(function ($subject) {
                    return [
                        'uuid' => $subject->uuid,
                        'name' => $subject->name,
                        'alias' => $subject->alias,
                        'code' => $subject->code,
                        'shortcode' => $subject->shortcode,
                        'type' => $subject->type,
                        'position' => $subject->position,
                        'has_grading' => $subject->has_grading,
                        'is_elective' => $subject->is_elective,
                        'has_no_exam' => $subject->has_no_exam,
                        'credit' => $subject->credit,
                        'exam_fee' => $subject->exam_fee,
                        'course_fee' => $subject->course_fee,
                        'max_class_per_week' => $subject->max_class_per_week,
                    ];
                })
                ->sortBy('position');

            return compact('subjects');
        });
    }
}
