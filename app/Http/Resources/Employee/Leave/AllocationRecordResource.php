<?php

namespace App\Http\Resources\Employee\Leave;

use Illuminate\Http\Resources\Json\JsonResource;

class AllocationRecordResource extends JsonResource
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
            'allocation' => AllocationResource::make($this->whenLoaded('allocation')),
            'leave_type' => TypeResource::make($this->whenLoaded('type')),
            'allotted' => $this->allotted,
            'used' => $this->used,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
