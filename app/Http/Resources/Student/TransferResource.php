<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $transferRequest = $this->getMeta('transfer_request') ? true : false;

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'number' => $this->number,
            'roll_number' => $this->roll_number,
            'code_number' => $this->code_number,
            'joining_date' => \Cal::date($this->joining_date),
            'leaving_date' => \Cal::date($this->leaving_date),
            'transfer_certificate_number' => $this->getMeta('transfer_certificate_number'),
            'transfer_request' => $transferRequest,
            'batch_uuid' => $this->batch_uuid,
            'course_uuid' => $this->course_uuid,
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'contact_number' => $this->contact_number,
            'birth_date' => \Cal::date($this->birth_date),
            'reason' => $this->reason,
            'reason_uuid' => $this->reason_uuid,
            'remarks' => $this->remarks,
            'leaving_remarks' => $this->leaving_remarks,
            'photo' => $this->photo_url,
            'start_date' => \Cal::date($this->start_date),
            'end_date' => \Cal::date($this->end_date),
            $this->mergeWhen($this->relationLoaded('admission'), [
                'media_token' => $this->admission->getMeta('media_token'),
                'media' => MediaResource::collection($this->getMedia()),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getMedia()
    {
        if (! $this->admission->relationLoaded('media')) {
            return [];
        }

        return $this->admission->media->where('meta.section', 'transfer_certificate');
    }
}
