<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            'code' => $this->code,
            'shortcode' => $this->shortcode,
            'alias' => $this->alias,
            'programs' => ProgramResource::collection($this->whenLoaded('programs')),
            $this->mergeWhen($request->query('details'), [
                'incharge' => DepartmentInchargeResource::make($this->whenLoaded('incharge')),
                'incharges' => DepartmentInchargeResource::collection($this->whenLoaded('incharges')),
            ]),
            'programs_count' => $this->programs_count,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
