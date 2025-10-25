<?php

namespace App\Services\Academic;

use App\Http\Resources\Academic\DivisionResource;
use App\Models\Academic\Course;
use App\Models\Academic\Division;
use App\Models\Academic\SubjectRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function preRequisite(Request $request)
    {
        $divisions = DivisionResource::collection(Division::query()
            ->select('divisions.*', 'programs.name as program_name')
            ->leftJoin('programs', 'programs.id', '=', 'divisions.program_id')
            ->byPeriod()
            ->get());

        return compact('divisions');
    }

    public function create(Request $request): Course
    {
        \DB::beginTransaction();

        $course = Course::forceCreate($this->formatParams($request));

        \DB::commit();

        return $course;
    }

    private function formatParams(Request $request, ?Course $course = null): array
    {
        $formatted = [
            'name' => $request->name,
            'term' => $request->term,
            'code' => $request->code,
            'shortcode' => $request->shortcode,
            'division_id' => $request->division_id,
            'enable_registration' => $request->boolean('enable_registration'),
            'registration_fee' => $request->registration_fee,
            'description' => $request->description,
        ];

        $meta = $course?->meta ?? [];

        $meta['pg_account'] = $request->pg_account;

        $config = $course?->config ?? [];
        $config['batch_with_same_subject'] = $request->boolean('batch_with_same_subject');
        $formatted['config'] = $config;

        if (! $course) {
            $formatted['position'] = $request->integer('position', 0);
        }

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Course $course): void
    {
        if ($request->batch_with_same_subject != $course->getConfig('batch_with_same_subject')) {
            $batchIds = $course->batches()->pluck('id')->all();

            $existingSubjects = SubjectRecord::query()
                ->whereIn('batch_id', $batchIds)
                ->exists();

            if ($existingSubjects) {
                throw ValidationException::withMessages(['batch_with_same_subject' => trans('academic.course.could_not_modify_same_subject_field_after_assigning_batch_wise_subject')]);
            }
        }

        \DB::beginTransaction();

        $course->forceFill($this->formatParams($request, $course))->save();

        \DB::commit();
    }

    public function deletable(Course $course): bool
    {
        $batchExists = \DB::table('batches')
            ->whereCourseId($course->id)
            ->exists();

        if ($batchExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.course.course'), 'dependency' => trans('academic.batch.batch')])]);
        }

        $subjectRecordExists = \DB::table('subject_records')
            ->whereCourseId($course->id)
            ->exists();

        if ($subjectRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.course.course'), 'dependency' => trans('academic.subject.subject')])]);
        }

        $feeAllocationExists = \DB::table('fee_allocations')
            ->whereCourseId($course->id)
            ->exists();

        if ($feeAllocationExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.course.course'), 'dependency' => trans('finance.fee_structure.allocation')])]);
        }

        return true;
    }
}
