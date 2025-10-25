<?php

namespace App\Http\Resources\Exam;

use App\Http\Resources\Academic\DivisionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TermResource extends JsonResource
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
            'display_name' => $this->display_name,
            'position' => $this->position,
            'division' => DivisionResource::make($this->whenLoaded('division')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
