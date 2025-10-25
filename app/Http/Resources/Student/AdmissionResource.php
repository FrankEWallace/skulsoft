<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Academic\BatchResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
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
            'code_number' => $this->code_number,
            'provisional_code_number' => $this->provisional_code_number,
            'is_provisional' => $this->is_provisional,
            'registration' => RegistrationResource::make($this->whenLoaded('registration')),
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'joining_date' => $this->joining_date,
            'remarks' => $this->remarks,
            'leaving_date' => $this->leaving_date,
            'leaving_remarks' => $this->leaving_remarks,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
