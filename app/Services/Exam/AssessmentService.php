<?php

namespace App\Services\Exam;

use App\Models\Exam\Assessment;
use App\Models\Exam\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    public function create(Request $request): Assessment
    {
        \DB::beginTransaction();

        $assessment = Assessment::forceCreate($this->formatParams($request));

        \DB::commit();

        return $assessment;
    }

    private function formatParams(Request $request, ?Assessment $assessment = null): array
    {
        $formatted = [
            'name' => $request->name,
            'records' => collect($request->records)->map(function ($record, $index) {
                return [
                    'position' => $index + 1,
                    'name' => Arr::get($record, 'name'),
                    'code' => Arr::get($record, 'code'),
                    'max_mark' => Arr::get($record, 'max_mark'),
                    'passing_mark' => Arr::get($record, 'passing_mark'),
                    'description' => Arr::get($record, 'description'),
                ];
            })->toArray(),
            'description' => $request->description,
        ];

        if (! $assessment) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Assessment $assessment): void
    {
        $scheduleExists = Schedule::query()
            ->whereAssessmentId($assessment->id)
            ->exists();

        if ($scheduleExists) {
            $existingAssessmentCodes = collect($assessment->records)->pluck('code')->toArray();

            $newAssessmentCodes = collect($request->records)->pluck('code')->toArray();

            if (array_diff($existingAssessmentCodes, $newAssessmentCodes)) {
                throw ValidationException::withMessages(['message' => trans('exam.assessment.could_not_change_code_after_mark_recorded')]);
            }
        }

        \DB::beginTransaction();

        $assessment->forceFill($this->formatParams($request, $assessment))->save();

        \DB::commit();
    }

    public function deletable(Assessment $assessment): bool
    {
        $examScheduleExists = \DB::table('exam_schedules')
            ->whereAssessmentId($assessment->id)
            ->exists();

        if ($examScheduleExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('exam.assessment.assessment'), 'dependency' => trans('exam.schedule.schedule')])]);
        }

        return true;
    }
}
