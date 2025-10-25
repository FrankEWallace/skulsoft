<?php

namespace App\Services\Exam;

use App\Enums\Exam\OnlineExamType;
use App\Helpers\CalHelper;
use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Academic\Subject;
use App\Models\Employee\Employee;
use App\Models\Exam\OnlineExam;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OnlineExamService
{
    public function preRequisite(Request $request)
    {
        $subjects = SubjectResource::collection(Subject::query()
            ->byPeriod()
            ->get());

        $types = OnlineExamType::getOptions();

        return compact('types', 'subjects');
    }

    public function create(Request $request): OnlineExam
    {
        \DB::beginTransaction();

        $onlineExam = OnlineExam::forceCreate($this->formatParams($request));

        $this->updateBatchSubjectRecords($request, $onlineExam);

        $onlineExam->addMedia($request);

        \DB::commit();

        return $onlineExam;
    }

    private function formatParams(Request $request, ?OnlineExam $onlineExam = null): array
    {
        $startTime = CalHelper::storeDateTime($request->date.' '.$request->start_time)?->toTimeString();
        $endTime = CalHelper::storeDateTime($request->date.' '.$request->end_time)?->toTimeString();

        $formatted = [
            'title' => $request->title,
            'type' => $request->type,
            'date' => $request->date,
            'start_time' => $startTime,
            'end_date' => $request->end_date ?: $request->date,
            'end_time' => $endTime,
            'pass_percentage' => $request->float('pass_percentage', 0),
            'instructions' => clean($request->instructions),
            'description' => clean($request->description),
        ];

        if (! $onlineExam) {
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['employee_id'] = Employee::auth()->first()?->id;
        }

        $config = $onlineExam?->config ?? [];

        $config['has_negative_marking'] = $request->boolean('has_negative_marking');
        $config['negative_mark_percent_per_question'] = $request->float('negative_mark_percent_per_question', 0);
        $formatted['config'] = $config;

        return $formatted;
    }

    private function updateBatchSubjectRecords(Request $request, OnlineExam $onlineExam)
    {
        $usedIds = [];
        foreach ($request->batch_ids as $batchId) {
            $usedIds[] = [
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ];
            BatchSubjectRecord::firstOrCreate([
                'model_type' => $onlineExam->getMorphClass(),
                'model_id' => $onlineExam->id,
                'batch_id' => $batchId,
                'subject_id' => $request->subject_id,
            ]);
        }
        $records = BatchSubjectRecord::query()
            ->whereModelType($onlineExam->getMorphClass())
            ->whereModelId($onlineExam->id)
            ->get();
        $usedIds = collect($usedIds);
        foreach ($records as $record) {
            if (! $usedIds->where('batch_id', $record->batch_id)->where('subject_id', $record->subject_id)->count()) {
                $record->delete();
            }
        }
    }

    public function update(Request $request, OnlineExam $onlineExam): void
    {
        if (! $onlineExam->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        \DB::beginTransaction();

        $onlineExam->forceFill($this->formatParams($request, $onlineExam))->save();

        $this->updateBatchSubjectRecords($request, $onlineExam);

        $onlineExam->updateMedia($request);

        \DB::commit();
    }

    public function deletable(OnlineExam $onlineExam): bool
    {
        if (! $onlineExam->is_deletable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        // $onlineExamScheduleExists = \DB::table('exam_schedules')
        //     ->whereExamId($onlineExam->id)
        //     ->exists();

        // if ($onlineExamScheduleExists) {
        //     throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('exam.exam'), 'dependency' => trans('exam.schedule.schedule')])]);
        // }

        return true;
    }
}
