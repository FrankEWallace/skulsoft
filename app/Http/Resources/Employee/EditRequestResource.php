<?php

namespace App\Http\Resources\Employee;

use App\Enums\ContactEditStatus;
use App\Http\Resources\MediaResource;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EditRequestResource extends JsonResource
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
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('model')),
            'user' => UserSummaryResource::make($this->whenLoaded('user')),
            'status' => ContactEditStatus::getDetail($this->status),
            'is_rejected' => $this->status == ContactEditStatus::REJECTED ? true : false,
            'comment' => $this->comment,
            'data' => $this->data,
            'processed_at' => \Cal::dateTime($this->processed_at),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
