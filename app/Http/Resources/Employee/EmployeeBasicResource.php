<?php

namespace App\Http\Resources\Employee;

use App\Enums\Employee\Type;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeBasicResource extends JsonResource
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
            'type' => Type::getDetail($this->type),
            'code_number' => $this->code_number,
            'name' => $this->name,
            'designation' => $this->designation_name ?? '-',
            'team_name' => $this->team_name ?? '-',
        ];
    }
}
