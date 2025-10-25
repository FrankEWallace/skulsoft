<?php

namespace App\Http\Resources\Recruitment;

use App\Http\Resources\Employee\DesignationResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class VacancyRecordResource extends JsonResource
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
            'employment_type' => OptionResource::make($this->whenLoaded('employmentType')),
            'designation' => DesignationResource::make($this->whenLoaded('designation')),
            'number_of_positions' => $this->number_of_positions,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
