<?php

namespace App\Services\Exam;

use App\Http\Resources\Exam\GradeResource;
use App\Models\Exam\Grade;
use App\Models\Exam\Observation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ObservationService
{
    public function preRequisite(Request $request)
    {
        $grades = GradeResource::collection(Grade::query()
            ->byPeriod()
            ->get());

        return compact('grades');
    }

    public function create(Request $request): Observation
    {
        \DB::beginTransaction();

        $observation = Observation::forceCreate($this->formatParams($request));

        \DB::commit();

        return $observation;
    }

    private function formatParams(Request $request, ?Observation $observation = null): array
    {
        $formatted = [
            'name' => $request->name,
            'grade_id' => $request->grade_id,
            'records' => collect($request->records)->map(function ($record, $index) {
                return [
                    'position' => $index + 1,
                    'name' => Arr::get($record, 'name'),
                    'code' => Arr::get($record, 'code'),
                    'max_mark' => Arr::get($record, 'max_mark'),
                    'description' => Arr::get($record, 'description'),
                ];
            })->toArray(),
            'description' => $request->description,
        ];

        if (! $observation) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Observation $observation): void
    {
        \DB::beginTransaction();

        $observation->forceFill($this->formatParams($request, $observation))->save();

        \DB::commit();
    }

    public function deletable(Observation $observation): void
    {
        //
    }
}
