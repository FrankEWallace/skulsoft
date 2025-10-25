<?php

namespace App\Http\Resources\Resource;

use App\Concerns\HasViewLogs;
use App\Http\Resources\Academic\BatchSubjectRecordResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class DiaryResource extends JsonResource
{
    use HasViewLogs;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'records' => BatchSubjectRecordResource::collection($this->whenLoaded('records')),
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'date' => $this->date,
            'details' => collect($this->details)->map(function ($detail) {
                return [
                    'uuid' => (string) Str::uuid(),
                    'heading' => $detail['heading'],
                    'description' => $detail['description'],
                ];
            }),
            $this->mergeWhen(auth()->user()->can('student-diary:view-log'), [
                'view_logs' => $this->getViewLogs(),
            ]),
            'is_editable' => $this->is_editable,
            'is_deletable' => $this->is_deletable,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
