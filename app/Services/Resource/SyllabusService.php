<?php

namespace App\Services\Resource;

use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Academic\Subject;
use App\Models\Employee\Employee;
use App\Models\Resource\Syllabus;
use App\Models\Resource\SyllabusUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SyllabusService
{
    public function preRequisite(Request $request): array
    {
        $subjects = SubjectResource::collection(Subject::query()
            ->byPeriod()
            ->get());

        return compact('subjects');
    }

    public function create(Request $request): Syllabus
    {
        \DB::beginTransaction();

        $syllabus = Syllabus::forceCreate($this->formatParams($request));

        $this->updateBatchSubjectRecords($request, $syllabus);

        $this->updateUnits($request, $syllabus);

        $syllabus->addMedia($request);

        \DB::commit();

        return $syllabus;
    }

    private function formatParams(Request $request, ?Syllabus $syllabus = null): array
    {
        $formatted = [
            'remarks' => $request->remarks,
        ];

        if (! $syllabus) {
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['employee_id'] = Employee::auth()->first()?->id;
        }

        return $formatted;
    }

    private function updateUnits(Request $request, Syllabus $syllabus): void
    {
        $unitNames = [];
        foreach ($request->units as $unit) {
            $syllabusUnit = SyllabusUnit::firstOrCreate([
                'syllabus_id' => $syllabus->id,
                'unit_name' => Arr::get($unit, 'unit_name'),
            ]);

            $unitNames[] = Arr::get($unit, 'unit_name');

            $syllabusUnit->unit_number = Arr::get($unit, 'unit_number');
            $syllabusUnit->start_date = Arr::get($unit, 'start_date');
            $syllabusUnit->end_date = Arr::get($unit, 'end_date');
            $syllabusUnit->completion_date = Arr::get($unit, 'completion_date');
            $syllabusUnit->description = Arr::get($unit, 'description');
            $syllabusUnit->save();
        }

        SyllabusUnit::query()
            ->whereSyllabusId($syllabus->id)
            ->whereNotIn('unit_name', $unitNames)
            ->delete();
    }

    private function updateBatchSubjectRecords(Request $request, Syllabus $syllabus)
    {
        $usedIds = [];
        foreach ($request->batch_ids as $batchId) {
            $usedIds[] = [
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ];
            BatchSubjectRecord::firstOrCreate([
                'model_type' => $syllabus->getMorphClass(),
                'model_id' => $syllabus->id,
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ]);
        }
        $records = BatchSubjectRecord::query()
            ->whereModelType($syllabus->getMorphClass())
            ->whereModelId($syllabus->id)
            ->get();
        $usedIds = collect($usedIds);
        foreach ($records as $record) {
            if (! $usedIds->where('batch_id', $record->batch_id)->where('subject_id', $record->subject_id)->count()) {
                $record->delete();
            }
        }
    }

    public function update(Request $request, Syllabus $syllabus): void
    {
        \DB::beginTransaction();

        $syllabus->forceFill($this->formatParams($request, $syllabus))->save();

        $this->updateBatchSubjectRecords($request, $syllabus);

        $this->updateUnits($request, $syllabus);

        $syllabus->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Syllabus $syllabus): void
    {
        //
    }
}
