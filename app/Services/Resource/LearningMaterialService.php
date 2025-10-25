<?php

namespace App\Services\Resource;

use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Academic\Subject;
use App\Models\Employee\Employee;
use App\Models\Resource\LearningMaterial;
use App\Support\HasAudience;
use Illuminate\Http\Request;

class LearningMaterialService
{
    use HasAudience;

    public function preRequisite(Request $request): array
    {
        $subjects = SubjectResource::collection(Subject::query()
            ->byPeriod()
            ->get());

        return compact('subjects');
    }

    public function create(Request $request): LearningMaterial
    {
        \DB::beginTransaction();

        $learningMaterial = LearningMaterial::forceCreate($this->formatParams($request));

        $this->updateBatchSubjectRecords($request, $learningMaterial);

        $learningMaterial->addMedia($request);

        \DB::commit();

        return $learningMaterial;
    }

    private function formatParams(Request $request, ?LearningMaterial $learningMaterial = null): array
    {
        $formatted = [
            'title' => $request->title,
            'description' => clean($request->description),
            'published_at' => now()->toDateTimeString(),
        ];

        if (! $learningMaterial) {
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['employee_id'] = Employee::auth()->first()?->id;
        }

        return $formatted;
    }

    private function updateBatchSubjectRecords(Request $request, LearningMaterial $learningMaterial)
    {
        $usedIds = [];
        foreach ($request->batch_ids as $batchId) {
            $usedIds[] = [
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ];
            BatchSubjectRecord::firstOrCreate([
                'model_type' => $learningMaterial->getMorphClass(),
                'model_id' => $learningMaterial->id,
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ]);
        }
        $records = BatchSubjectRecord::query()
            ->whereModelType($learningMaterial->getMorphClass())
            ->whereModelId($learningMaterial->id)
            ->get();
        $usedIds = collect($usedIds);
        foreach ($records as $record) {
            if (! $usedIds->where('batch_id', $record->batch_id)->where('subject_id', $record->subject_id)->count()) {
                $record->delete();
            }
        }
    }

    public function update(Request $request, LearningMaterial $learningMaterial): void
    {
        \DB::beginTransaction();

        $learningMaterial->forceFill($this->formatParams($request, $learningMaterial))->save();

        $this->updateBatchSubjectRecords($request, $learningMaterial);

        $learningMaterial->updateMedia($request);

        \DB::commit();
    }

    public function deletable(LearningMaterial $learningMaterial): void
    {
        //
    }
}
