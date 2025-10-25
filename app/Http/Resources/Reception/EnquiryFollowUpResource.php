<?php

namespace App\Http\Resources\Reception;

use App\Enums\Reception\EnquiryStatus;
use App\Http\Resources\OptionResource;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EnquiryFollowUpResource extends JsonResource
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
            'follow_up_date' => $this->follow_up_date,
            'next_follow_up_date' => $this->next_follow_up_date,
            'status' => EnquiryStatus::getDetail($this->status),
            'stage' => OptionResource::make($this->whenLoaded('stage')),
            'remarks' => $this->remarks,
            'user' => UserSummaryResource::make($this->whenLoaded('user')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
