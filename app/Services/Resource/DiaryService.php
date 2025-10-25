<?php

namespace App\Services\Resource;

use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Academic\Subject;
use App\Models\Employee\Employee;
use App\Models\Resource\Diary;
use Illuminate\Http\Request;

class DiaryService
{
    public function preRequisite(Request $request): array
    {
        $subjects = SubjectResource::collection(Subject::query()
            ->byPeriod()
            ->get());

        return compact('subjects');
    }

    public function create(Request $request): Diary
    {
        \DB::beginTransaction();

        $diary = Diary::forceCreate($this->formatParams($request));

        $this->updateBatchSubjectRecords($request, $diary);

        $diary->addMedia($request);

        \DB::commit();

        return $diary;
    }

    private function formatParams(Request $request, ?Diary $diary = null): array
    {
        $formatted = [
            'date' => $request->date,
            'details' => collect($request->details)->map(function ($detail) {
                return [
                    'heading' => $detail['heading'],
                    'description' => $detail['description'],
                ];
            })->toArray(),
        ];

        if (! $diary) {
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['employee_id'] = Employee::auth()->first()?->id;
        }

        return $formatted;
    }

    private function updateBatchSubjectRecords(Request $request, Diary $diary)
    {
        $usedIds = [];
        foreach ($request->batch_ids as $batchId) {
            $usedIds[] = [
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ];

            BatchSubjectRecord::firstOrCreate([
                'model_type' => $diary->getMorphClass(),
                'model_id' => $diary->id,
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ]);
        }

        $records = BatchSubjectRecord::query()
            ->whereModelType($diary->getMorphClass())
            ->whereModelId($diary->id)
            ->get();

        $usedIds = collect($usedIds);
        foreach ($records as $record) {
            if (! $usedIds->where('batch_id', $record->batch_id)->where('subject_id', $record->subject_id)->count()) {
                $record->delete();
            }
        }
    }

    public function update(Request $request, Diary $diary): void
    {
        \DB::beginTransaction();

        $diary->forceFill($this->formatParams($request, $diary))->save();

        $this->updateBatchSubjectRecords($request, $diary);

        $diary->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Diary $diary): void
    {
        //
    }
}
