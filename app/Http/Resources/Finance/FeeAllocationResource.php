<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\Academic\BatchResource;
use App\Http\Resources\Academic\CourseResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeAllocationResource extends JsonResource
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
            'course' => CourseResource::make($this->whenLoaded('course')),
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
