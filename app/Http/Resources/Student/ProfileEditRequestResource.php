<?php

namespace App\Http\Resources\Student;

use App\Enums\ContactEditStatus;
use App\Http\Resources\ContactSummaryResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileEditRequestResource extends JsonResource
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
            'user' => UserSummaryResource::make($this->whenLoaded('user')),
            'contact' => ContactSummaryResource::make($this->model->contact),
            'status' => ContactEditStatus::getDetail($this->status),
            'is_rejected' => $this->status == ContactEditStatus::REJECTED ? true : false,
            'is_approved' => $this->status == ContactEditStatus::APPROVED ? true : false,
            'comment' => $this->comment,
            'processed_at' => \Cal::dateTime($this->processed_at),
            'data' => [
                'new' => collect($this->data['new'] ?? [])->map(function ($item, $key) {
                    if (str_contains($key, '_date')) {
                        $item = \Cal::date($item);
                    }

                    return $item;
                })->toArray(),
                'old' => collect($this->data['old'] ?? [])->map(function ($item, $key) {
                    if (str_contains($key, '_date')) {
                        $item = \Cal::date($item);
                    }

                    return $item;
                })->toArray(),
            ],
            'processed_by' => $this->getMeta('processed_by'),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
