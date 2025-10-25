<?php

namespace App\Services\Academic;

use App\Models\Academic\Batch;
use App\Models\Academic\Course;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubjectRecordService
{
    public function create(Request $request, Subject $subject): SubjectRecord
    {
        $methodName = 'allocate'.$request->type;

        if (! method_exists($this, $methodName)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }
        \DB::beginTransaction();

        $subjectRecord = $this->$methodName($request, $subject);

        \DB::commit();

        return $subjectRecord;
    }

    private function allocateCourse(Request $request, Subject $subject)
    {
        // $existingCourses = SubjectRecord::query()
        //     ->with('course:id,name')
        //     ->whereIn('course_id', $request->course_ids)
        //     ->where('subject_id', '!=', $subject->id)
        //     ->get()
        //     ->pluck('course.name')
        //     ->all();

        // if (count($existingCourses)) {
        //     throw ValidationException::withMessages(['courses' => trans('academic.subject.already_allocated', ['attribute' => implode(", ", $existingCourses)])]);
        // }

        $batchIds = Batch::query()
            ->whereIn('course_id', $request->course_ids)
            ->pluck('id')
            ->all();

        $existingBatches = SubjectRecord::query()
            ->with('batch:id,name,course_id', 'batch.course:id,name')
            ->whereSubjectId($subject->id)
            ->whereIn('batch_id', $batchIds)
            ->get();

        if ($existingBatches->count()) {
            $batchNames = $existingBatches->map(function ($feeAllocation) {
                return $feeAllocation->batch->course->name.' '.$feeAllocation->batch->name;
            })->all();

            $batchNames = array_unique($batchNames);

            throw ValidationException::withMessages(['courses' => trans('academic.subject.already_allocated', ['attribute' => implode(', ', $batchNames)])]);
        }

        foreach ($request->course_ids as $course_id) {
            $subjectRecord = SubjectRecord::firstOrCreate([
                'subject_id' => $subject->id,
                'course_id' => $course_id,
            ]);

            $subjectRecord->credit = $request->credit;
            $subjectRecord->max_class_per_week = $request->max_class_per_week;
            $subjectRecord->exam_fee = $request->exam_fee;
            $subjectRecord->course_fee = $request->course_fee;
            $subjectRecord->is_elective = $request->boolean('is_elective');
            $subjectRecord->has_no_exam = $request->boolean('has_no_exam');
            $subjectRecord->has_grading = $request->boolean('has_grading');
            $subjectRecord->save();
        }

        return $subjectRecord;
    }

    private function allocateBatch(Request $request, Subject $subject)
    {
        // $existingBatches = SubjectRecord::query()
        //     ->with('batch:id,name,course_id', 'batch.course:id,name')
        //     ->whereIn('batch_id', $request->batch_ids)
        //     ->where('subject_id', '!=', $subject->id)
        //     ->get();

        // if (count($existingBatches)) {

        //     $batches = [];
        //     foreach ($existingBatches as $feeAllocation) {
        //         $batches[] = $feeAllocation->batch->course->name.' '.$feeAllocation->batch->name;
        //     }

        //     throw ValidationException::withMessages(['batches' => trans('academic.subject.already_allocated', ['attribute' => implode(", ", $batches)])]);
        // }

        $courseIds = Course::query()
            ->whereHas('batches', function ($q) use ($request) {
                $q->whereIn('id', $request->batch_ids);
            })
            ->pluck('id')
            ->all();

        $existingCourses = SubjectRecord::query()
            ->with('course:id,name')
            ->whereSubjectId($subject->id)
            ->whereIn('course_id', $courseIds)
            ->get();

        if ($existingCourses->count()) {
            throw ValidationException::withMessages(['batches' => trans('academic.subject.already_allocated', ['attribute' => implode(', ', array_unique($existingCourses->pluck('course.name')->all()))])]);
        }

        foreach ($request->batch_ids as $batch_id) {
            $subjectRecord = SubjectRecord::firstOrCreate([
                'subject_id' => $subject->id,
                'batch_id' => $batch_id,
            ]);

            $subjectRecord->credit = $request->credit;
            $subjectRecord->max_class_per_week = $request->max_class_per_week;
            $subjectRecord->exam_fee = $request->exam_fee;
            $subjectRecord->course_fee = $request->course_fee;
            $subjectRecord->is_elective = $request->boolean('is_elective');
            $subjectRecord->has_no_exam = $request->boolean('has_no_exam');
            $subjectRecord->has_grading = $request->boolean('has_grading');
            $subjectRecord->save();
        }

        return $subjectRecord;
    }

    public function update(Request $request, Subject $subject, SubjectRecord $subjectRecord): void
    {
        throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        \DB::beginTransaction();

        $subjectRecord->forceFill([
            'credit' => $request->credit,
            'max_class_per_week' => $request->max_class_per_week,
            'is_elective' => $request->boolean('is_elective'),
            'has_no_exam' => $request->boolean('has_no_exam'),
        ])->save();

        \DB::commit();
    }

    public function deletable(Subject $subject, SubjectRecord $subjectRecord): void
    {
        $batchIds = $subjectRecord->batch_id ? [$subjectRecord->batch_id] : Batch::query()
            ->whereCourseId($subjectRecord->course_id)->pluck('id')->all();

        $studentExists = \DB::table('subject_wise_students')
            ->whereIn('batch_id', $batchIds)
            ->whereSubjectId($subject->id)
            ->exists();

        if ($studentExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.subject.subject'), 'dependency' => trans('student.student')])]);
        }
    }
}
