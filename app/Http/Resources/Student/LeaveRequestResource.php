<?php

namespace App\Http\Resources\Student;

use App\Enums\Student\LeaveRequestStatus;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'student' => StudentSummaryResource::make($this->whenLoaded('model')),
            'category' => OptionResource::make($this->whenLoaded('category')),
            'requester' => UserSummaryResource::make($this->whenLoaded('requester')),
            'reason' => $this->reason,
            'period' => $this->period,
            'duration' => $this->duration,
            'status' => LeaveRequestStatus::getDetail($this->status),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_editable' => $this->is_editable,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
