<?php

namespace App\Http\Resources\Employee\Payroll;

use App\Http\Resources\TeamResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryTemplateResource extends JsonResource
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
            'alias' => $this->alias,
            'structures_count' => $this->structures_count,
            'team' => TeamResource::make($this->whenLoaded('team')),
            'records' => SalaryTemplateRecordResource::collection($this->whenLoaded('records')),
            'has_hourly_payroll' => $this->has_hourly_payroll,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
