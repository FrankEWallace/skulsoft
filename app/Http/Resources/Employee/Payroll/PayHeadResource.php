<?php

namespace App\Http\Resources\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadCategory;
use App\Http\Resources\TeamResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PayHeadResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'alias' => $this->alias,
            'category' => PayHeadCategory::getDetail($this->category),
            'team' => TeamResource::make($this->whenLoaded('team')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
