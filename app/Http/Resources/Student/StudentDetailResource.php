<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Academic\BatchResource;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDetailResource extends JsonResource
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
            'roll_number' => $this->roll_number,
            'admission' => AdmissionResource::make($this->whenLoaded('admission')),
            'contact' => ContactResource::make($this->whenLoaded('contact')),
            'period' => PeriodResource::make($this->whenLoaded('period')),
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'enrollment_type' => OptionResource::make($this->whenLoaded('enrollmentType')),
            'enrollment_status' => OptionResource::make($this->whenLoaded('enrollmentStatus')),
            'mentor' => EmployeeSummaryResource::make($this->whenLoaded('mentor')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
