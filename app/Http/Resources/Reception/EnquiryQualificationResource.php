<?php

namespace App\Http\Resources\Reception;

use App\Enums\QualificationResult;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EnquiryQualificationResource extends JsonResource
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
            'course' => $this->course,
            'session' => $this->getMeta('session'),
            'institute' => $this->institute,
            'institute_address' => $this->getMeta('institute_address'),
            'affiliated_to' => $this->affiliated_to,
            'level' => OptionResource::make($this->whenLoaded('level')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'result' => QualificationResult::getDetail($this->result),
            'total_marks' => $this->getMeta('total_marks'),
            'obtained_marks' => $this->getMeta('obtained_marks'),
            'percentage' => $this->getMeta('percentage'),
            'failed_subjects' => $this->getMeta('failed_subjects'),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
