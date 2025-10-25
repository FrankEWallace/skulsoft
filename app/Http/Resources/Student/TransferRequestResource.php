<?php

namespace App\Http\Resources\Student;

use App\Enums\Student\TransferRequestStatus;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferRequestResource extends JsonResource
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
            'code_number' => $this->code_number,
            'student' => StudentSummaryResource::make($this->whenLoaded('student')),
            'reason' => $this->reason,
            'comment' => $this->getMeta('comment'),
            'transfer_certificate_number' => $this->getMeta('transfer_certificate_number'),
            'processed_by' => $this->getMeta('processed_by'),
            'status' => TransferRequestStatus::getDetail($this->status),
            'request_date' => $this->request_date,
            'processed_at' => $this->processed_at,
            'is_editable' => $this->is_editable,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
