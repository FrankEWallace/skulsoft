<?php

namespace App\Http\Resources\Reception;

use App\Enums\Reception\ComplaintStatus;
use App\Http\Resources\UserBasicResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintLogResource extends JsonResource
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
            'action' => $this->action,
            'comment' => $this->comment,
            'status' => ComplaintStatus::getDetail($this->status),
            'employee' => UserBasicResource::make($this->user),
            $this->mergeWhen(! auth()->user()->hasAnyRole(['student', 'guardian']), [
                'remarks' => $this->remarks,
            ]),
            // 'media_token' => $this->getMeta('media_token'),
            // 'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
