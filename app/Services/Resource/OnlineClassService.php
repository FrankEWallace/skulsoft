<?php

namespace App\Services\Resource;

use App\Enums\Resource\OnlineClassPlatform;
use App\Helpers\CalHelper;
use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Academic\Subject;
use App\Models\Employee\Employee;
use App\Models\Resource\OnlineClass;
use App\Support\HasAudience;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OnlineClassService
{
    use HasAudience;

    public function preRequisite(Request $request): array
    {
        $platforms = OnlineClassPlatform::getOptions();

        $subjects = SubjectResource::collection(Subject::query()
            ->byPeriod()
            ->get());

        return compact('platforms', 'subjects');
    }

    public function create(Request $request): OnlineClass
    {
        \DB::beginTransaction();

        $onlineClass = OnlineClass::forceCreate($this->formatParams($request));

        $this->updateBatchSubjectRecords($request, $onlineClass);

        $onlineClass->addMedia($request);

        \DB::commit();

        return $onlineClass;
    }

    private function formatParams(Request $request, ?OnlineClass $onlineClass = null): array
    {
        $formatted = [
            'topic' => $request->topic,
            'description' => clean($request->description),
            'start_at' => CalHelper::storeDateTime($request->start_at)->toDateTimeString(),
            'duration' => $request->duration,
            'platform' => $request->platform,
            'meeting_code' => config('config.resource.online_class_use_meeting_code') ? $request->meeting_code : null,
            'url' => ! config('config.resource.online_class_use_meeting_code') ? $request->url : null,
            'password' => $request->password,
        ];

        if (! $onlineClass) {
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['employee_id'] = Employee::auth()->first()?->id;
        }

        return $formatted;
    }

    private function updateBatchSubjectRecords(Request $request, OnlineClass $onlineClass)
    {
        $usedIds = [];
        foreach ($request->batch_ids as $batchId) {
            $usedIds[] = [
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ];
            BatchSubjectRecord::firstOrCreate([
                'model_type' => $onlineClass->getMorphClass(),
                'model_id' => $onlineClass->id,
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ]);
        }
        $records = BatchSubjectRecord::query()
            ->whereModelType($onlineClass->getMorphClass())
            ->whereModelId($onlineClass->id)
            ->get();
        $usedIds = collect($usedIds);
        foreach ($records as $record) {
            if (! $usedIds->where('batch_id', $record->batch_id)->where('subject_id', $record->subject_id)->count()) {
                $record->delete();
            }
        }
    }

    public function update(Request $request, OnlineClass $onlineClass): void
    {
        \DB::beginTransaction();

        if ($onlineClass->status != 'ended') {
            $onlineClass->forceFill($this->formatParams($request, $onlineClass))->save();

            $this->updateBatchSubjectRecords($request, $onlineClass);
        }

        $onlineClass->updateMedia($request);

        \DB::commit();
    }

    public function deletable(OnlineClass $onlineClass): void
    {
        if ($onlineClass->end_at->value > now()->toDateTimeString()) {
            throw ValidationException::withMessages(['message' => trans('resource.online_class.could_not_delete_ended_class')]);
        }
    }
}
