<?php

namespace App\Services\Finance;

use App\Models\Academic\Batch;
use App\Models\Academic\Course;
use App\Models\Finance\FeeAllocation;
use App\Models\Finance\FeeStructure;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeeStructureActionService
{
    public function allocation(Request $request, FeeStructure $feeStructure): void
    {
        $methodName = 'allocate'.$request->type;

        if (! method_exists($this, $methodName)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $this->$methodName($request, $feeStructure);
    }

    private function allocateCourse(Request $request, FeeStructure $feeStructure)
    {
        $existingCourseAllocations = FeeAllocation::query()
            ->with('course:id,name')
            ->whereIn('course_id', $request->course_ids)
            ->where('fee_structure_id', '!=', $feeStructure->id)
            ->get()
            ->pluck('course.name')
            ->all();

        if (count($existingCourseAllocations)) {
            throw ValidationException::withMessages(['courses' => trans('finance.fee_structure.fee_already_allocated', ['attribute' => implode(', ', $existingCourseAllocations)])]);
        }

        $batchIds = Batch::query()
            ->whereIn('course_id', $request->course_ids)
            ->pluck('id')
            ->all();

        $existingBatchAllocations = FeeAllocation::query()
            ->with('batch:id,name,course_id', 'batch.course:id,name')
            ->whereIn('batch_id', $batchIds)
            ->get();

        if ($existingBatchAllocations->count()) {
            $batchNames = $existingBatchAllocations->map(function ($feeAllocation) {
                return $feeAllocation->batch->course->name.' '.$feeAllocation->batch->name;
            })->all();

            throw ValidationException::withMessages(['courses' => trans('finance.fee_structure.fee_already_allocated', ['attribute' => implode(', ', $batchNames)])]);
        }

        foreach ($request->course_ids as $course_id) {
            FeeAllocation::firstOrCreate([
                'fee_structure_id' => $feeStructure->id,
                'course_id' => $course_id,
            ]);
        }
    }

    private function allocateBatch(Request $request, FeeStructure $feeStructure)
    {
        $existingBatchAllocations = FeeAllocation::query()
            ->with('batch:id,name,course_id', 'batch.course:id,name')
            ->whereIn('batch_id', $request->batch_ids)
            ->where('fee_structure_id', '!=', $feeStructure->id)
            ->get();

        if (count($existingBatchAllocations)) {

            $batches = [];
            foreach ($existingBatchAllocations as $feeAllocation) {
                $batches[] = $feeAllocation->batch->course->name.' '.$feeAllocation->batch->name;
            }

            throw ValidationException::withMessages(['batches' => trans('finance.fee_structure.fee_already_allocated', ['attribute' => implode(', ', $batches)])]);
        }

        $courseIds = Course::query()
            ->whereHas('batches', function ($q) use ($request) {
                $q->whereIn('id', $request->batch_ids);
            })
            ->pluck('id')
            ->all();

        $existingCourseAllocations = FeeAllocation::query()
            ->with('course:id,name')
            ->whereIn('course_id', $courseIds)
            ->get();

        if ($existingCourseAllocations->count()) {
            throw ValidationException::withMessages(['batches' => trans('finance.fee_structure.fee_already_allocated', ['attribute' => implode(', ', $existingCourseAllocations->pluck('course.name')->all())])]);
        }

        foreach ($request->batch_ids as $batch_id) {
            FeeAllocation::firstOrCreate([
                'fee_structure_id' => $feeStructure->id,
                'batch_id' => $batch_id,
            ]);
        }
    }

    public function removeAllocation(Request $request, FeeStructure $feeStructure, string $uuid): void
    {
        $feeAllocation = FeeAllocation::query()
            ->where('fee_structure_id', $feeStructure->id)
            ->where('uuid', $uuid)
            ->getOrFail(trans('finance.fee_structure.allocation'), 'message');

        $allocatedFee = Student::query()
            ->whereFeeStructureId($feeStructure->id)
            ->when($feeAllocation->batch_id, function ($q, $batchId) {
                $q->where('batch_id', $batchId);
            })
            ->when($feeAllocation->course_id, function ($q, $courseId) {
                $q->whereHas('batch', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                });
            })
            ->exists();

        if ($allocatedFee) {
            throw ValidationException::withMessages(['message' => trans('finance.fee_structure.could_not_delete_allocation_if_allocated')]);
        }

        $feeAllocation->delete();
    }
}
