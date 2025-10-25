<?php

namespace App\Services\Resource;

use App\Enums\Resource\LessonPlanStatus;
use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Academic\Subject;
use App\Models\Employee\Employee;
use App\Models\Resource\LessonPlan;
use Illuminate\Http\Request;

class LessonPlanService
{
    public function preRequisite(Request $request): array
    {
        $subjects = SubjectResource::collection(Subject::query()
            ->byPeriod()
            ->get());

        $statuses = LessonPlanStatus::getOptions();

        return compact('statuses', 'subjects');
    }

    public function create(Request $request): LessonPlan
    {
        \DB::beginTransaction();

        $lessonPlan = LessonPlan::forceCreate($this->formatParams($request));

        $this->updateBatchSubjectRecords($request, $lessonPlan);

        $lessonPlan->addMedia($request);

        \DB::commit();

        return $lessonPlan;
    }

    private function formatParams(Request $request, ?LessonPlan $lessonPlan = null): array
    {
        $formatted = [
            'topic' => $request->topic,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'details' => collect($request->details)->map(function ($detail) {
                return [
                    'heading' => $detail['heading'],
                    'description' => $detail['description'],
                ];
            })->toArray(),
        ];

        if (! $lessonPlan) {
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['status'] = LessonPlanStatus::PUBLISHED;
            $formatted['employee_id'] = Employee::auth()->first()?->id;
        }

        return $formatted;
    }

    private function updateBatchSubjectRecords(Request $request, LessonPlan $lessonPlan)
    {
        $usedIds = [];
        foreach ($request->batch_ids as $batchId) {
            $usedIds[] = [
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ];
            BatchSubjectRecord::firstOrCreate([
                'model_type' => $lessonPlan->getMorphClass(),
                'model_id' => $lessonPlan->id,
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ]);
        }
        $records = BatchSubjectRecord::query()
            ->whereModelType($lessonPlan->getMorphClass())
            ->whereModelId($lessonPlan->id)
            ->get();
        $usedIds = collect($usedIds);
        foreach ($records as $record) {
            if (! $usedIds->where('batch_id', $record->batch_id)->where('subject_id', $record->subject_id)->count()) {
                $record->delete();
            }
        }
    }

    public function update(Request $request, LessonPlan $lessonPlan): void
    {
        \DB::beginTransaction();

        $lessonPlan->forceFill($this->formatParams($request, $lessonPlan))->save();

        $this->updateBatchSubjectRecords($request, $lessonPlan);

        $lessonPlan->updateMedia($request);

        \DB::commit();
    }

    public function deletable(LessonPlan $lessonPlan): void
    {
        //
    }
}
