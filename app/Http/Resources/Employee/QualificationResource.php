<?php

namespace App\Http\Resources\Employee;

use App\Enums\VerificationStatus;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class QualificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $selfUpload = (bool) $this->getMeta('self_upload');

        return [
            'uuid' => $this->uuid,
            'course' => $this->course,
            'institute' => $this->institute,
            'affiliated_to' => $this->affiliated_to,
            'level' => OptionResource::make($this->whenLoaded('level')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'result' => $this->result,
            'is_verified' => $this->is_verified,
            'self_upload' => $selfUpload,
            $this->mergeWhen($selfUpload, [
                'verification_status' => VerificationStatus::getDetail($this->verification_status),
                'verified_at' => $this->verified_at,
                'comment' => $this->getMeta('comment'),
            ]),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
