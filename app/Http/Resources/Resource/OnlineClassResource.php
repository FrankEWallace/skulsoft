<?php

namespace App\Http\Resources\Resource;

use App\Enums\Resource\OnlineClassPlatform;
use App\Enums\Resource\OnlineClassStatus;
use App\Http\Resources\Academic\BatchSubjectRecordResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class OnlineClassResource extends JsonResource
{
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
            'topic' => $this->topic,
            'topic_excerpt' => Str::summary($this->topic, 100),
            'description' => $this->description,
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'records' => BatchSubjectRecordResource::collection($this->whenLoaded('records')),
            'start_at' => $this->start_at,
            'duration' => $this->duration,
            'end_at' => $this->end_at,
            'platform' => OnlineClassPlatform::getDetail($this->platform->value),
            'show_url' => $this->show_url,
            'meeting_code' => $this->show_url ? $this->meeting_code : config('app.mask'),
            'url' => $this->show_url ? $this->url : config('app.mask'),
            'password' => $this->show_url ? $this->password : config('app.mask'),
            'meeting_url' => $this->show_url ? $this->meeting_url : config('app.mask'),
            'status' => OnlineClassStatus::getDetail($this->status),
            'is_editable' => $this->is_editable,
            'is_deletable' => $this->is_deletable,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
