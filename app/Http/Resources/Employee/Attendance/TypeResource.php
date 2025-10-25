<?php

namespace App\Http\Resources\Employee\Attendance;

use App\Enums\Employee\Attendance\Category as AttendanceCategory;
use App\Enums\Employee\Attendance\ProductionUnit as AttendanceProductionUnit;
use App\Http\Resources\TeamResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeResource extends JsonResource
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
            'unit' => AttendanceProductionUnit::getDetail($this->unit),
            'category' => AttendanceCategory::getDetail($this->category),
            'alias' => $this->alias,
            'team' => TeamResource::make($this->whenLoaded('team')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
