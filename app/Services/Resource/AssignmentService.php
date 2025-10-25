<?php

namespace App\Services\Resource;

use App\Enums\OptionType;
use App\Http\Resources\Academic\SubjectResource;
use App\Http\Resources\OptionResource;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Academic\Subject;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\Resource\Assignment;
use Illuminate\Http\Request;

class AssignmentService
{
    public function preRequisite(Request $request): array
    {
        $subjects = SubjectResource::collection(Subject::query()
            ->byPeriod()
            ->get());

        $types = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::ASSIGNMENT_TYPE->value)
            ->get());

        return compact('types', 'subjects');
    }

    public function create(Request $request): Assignment
    {
        \DB::beginTransaction();

        $assignment = Assignment::forceCreate($this->formatParams($request));

        $this->updateBatchSubjectRecords($request, $assignment);

        $assignment->addMedia($request);

        \DB::commit();

        return $assignment;
    }

    private function formatParams(Request $request, ?Assignment $assignment = null): array
    {
        $formatted = [
            'title' => $request->title,
            'type_id' => $request->type_id,
            'date' => $request->date,
            'due_date' => $request->due_date,
            'enable_marking' => $request->boolean('enable_marking'),
            'max_mark' => $request->boolean('enable_marking') ? $request->max_mark : 0,
            'description' => clean($request->description),
        ];

        if (! $assignment) {
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['published_at'] = now()->toDateTimeString();
            $formatted['employee_id'] = Employee::auth()->first()?->id;
        }

        return $formatted;
    }

    private function updateBatchSubjectRecords(Request $request, Assignment $assignment)
    {
        $usedIds = [];
        foreach ($request->batch_ids as $batchId) {
            $usedIds[] = [
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ];
            BatchSubjectRecord::firstOrCreate([
                'model_type' => $assignment->getMorphClass(),
                'model_id' => $assignment->id,
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ]);
        }
        $records = BatchSubjectRecord::query()
            ->whereModelType($assignment->getMorphClass())
            ->whereModelId($assignment->id)
            ->get();
        $usedIds = collect($usedIds);
        foreach ($records as $record) {
            if (! $usedIds->where('batch_id', $record->batch_id)->where('subject_id', $record->subject_id)->count()) {
                $record->delete();
            }
        }
    }

    public function update(Request $request, Assignment $assignment): void
    {
        \DB::beginTransaction();

        $assignment->forceFill($this->formatParams($request, $assignment))->save();

        $this->updateBatchSubjectRecords($request, $assignment);

        $assignment->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Assignment $assignment): void
    {
        //
    }
}
