<?php

namespace App\Http\Resources\Resource;

use App\Http\Resources\MediaResource;
use App\Http\Resources\Student\StudentSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentSubmissionResource extends JsonResource
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
            'description' => $this->description,
            'submitted_at' => $this->submitted_at,
            'student' => StudentSummaryResource::make($this->whenLoaded('student')),
            'obtained_mark' => $this->obtained_mark,
            'comment' => $this->comment,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
