<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
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
            'name' => $this->name,
            'name_with_department' => $this->name_with_department,
            'type' => ProgramTypeResource::make($this->whenLoaded('type')),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'code' => $this->code,
            'shortcode' => $this->shortcode,
            'alias' => $this->alias,
            $this->mergeWhen($request->query('details'), [
                'incharge' => ProgramInchargeResource::make($this->whenLoaded('incharge')),
                'incharges' => ProgramInchargeResource::collection($this->whenLoaded('incharges')),
            ]),
            'enable_registration' => (bool) $this->getConfig('enable_registration'),
            'periods' => PeriodResource::collection($this->whenLoaded('periods')),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
