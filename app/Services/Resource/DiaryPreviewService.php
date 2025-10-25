<?php

namespace App\Services\Resource;

use App\Actions\UpdateViewLog;
use App\Helpers\CalHelper;
use App\Http\Resources\Employee\EmployeeBasicResource;
use App\Http\Resources\MediaResource;
use App\Models\Academic\Batch;
use App\Models\Resource\Diary;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DiaryPreviewService
{
    public function preview(Request $request): array
    {
        $date = $request->date;

        if (! CalHelper::validateDate($request->date)) {
            $date = today()->toDateString();
        }

        $batch = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->where('uuid', $request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'message');

        $diaries = Diary::query()
            ->whereHas('records', function ($q) use ($batch) {
                $q->where('batch_subject_records.batch_id', $batch->id);
            })
            ->with([
                'records' => function ($q) use ($batch) {
                    $q->where('batch_id', $batch->id);
                },
                'records.subject',
                'employee' => fn ($q) => $q->summary(),
                'media',
            ])
            ->where('date', $date)
            ->get();

        $diary = [
            'date' => \Cal::date($date),
            'batch' => $batch->course->name.' '.$batch->name,
        ];

        $records = [];
        foreach ($diaries as $item) {

            (new UpdateViewLog)->handle($item);

            $record = $item->records()->first();
            $subject = $record?->subject?->name;

            $records['uuid'] = $item->uuid;
            $records['subject'] = $subject;
            $records['details'] = Arr::map($item->details, function ($detail) {
                return [
                    'uuid' => (string) Str::uuid(),
                    'heading' => Arr::get($detail, 'heading'),
                    'description' => Arr::get($detail, 'description'),
                ];
            });

            $records['employee'] = $item->employee ? EmployeeBasicResource::make($item?->employee) : null;
            $records['media'] = MediaResource::collection($item->media);

            $diary['records'][] = $records;
        }

        return $diary;
    }
}
