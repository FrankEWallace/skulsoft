<?php

namespace App\Services\Academic;

use App\Models\Academic\ClassTiming;
use App\Models\Academic\ClassTimingSession;
use App\Models\Academic\TimetableAllocation;
use App\Models\Academic\TimetableRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ClassTimingService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function create(Request $request): ClassTiming
    {
        \DB::beginTransaction();

        $classTiming = ClassTiming::forceCreate($this->formatParams($request));

        $this->updateSessions($request, $classTiming);

        \DB::commit();

        return $classTiming;
    }

    private function formatParams(Request $request, ?ClassTiming $classTiming = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        if (! $classTiming) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    private function updateSessions(Request $request, ClassTiming $classTiming): void
    {
        $names = [];
        foreach ($request->sessions as $session) {
            $names[] = Arr::get($session, 'name');

            $classTimingSession = ClassTimingSession::firstOrCreate([
                'class_timing_id' => $classTiming->id,
                'name' => Arr::get($session, 'name'),
            ]);

            $classTimingSession->is_break = Arr::get($session, 'is_break', false);
            $classTimingSession->start_time = Arr::get($session, 'start_time');
            $classTimingSession->end_time = Arr::get($session, 'end_time');
            $classTimingSession->save();
        }

        ClassTimingSession::query()
            ->where('class_timing_id', $classTiming->id)
            ->whereNotIn('name', $names)
            ->delete();
    }

    public function update(Request $request, ClassTiming $classTiming): void
    {
        $timetableRecords = TimetableRecord::query()
            ->whereClassTimingId($classTiming->id)
            ->get();

        if (TimetableAllocation::query()
            ->whereIn('timetable_record_id', $timetableRecords->pluck('id')->all())
            ->exists()) {
            throw ValidationException::withMessages(['message' => trans('academic.timetable.could_not_modify_if_allocated')]);
        }

        \DB::beginTransaction();

        $classTiming->forceFill($this->formatParams($request, $classTiming))->save();

        $this->updateSessions($request, $classTiming);

        \DB::commit();
    }

    public function deletable(ClassTiming $classTiming, $validate = false): ?bool
    {
        $timetableExists = TimetableRecord::query()
            ->whereClassTimingId($classTiming->id)
            ->exists();

        if ($timetableExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.class_timing.class_timing'), 'dependency' => trans('academic.timetable.timetable')])]);
        }

        return true;
    }
}
