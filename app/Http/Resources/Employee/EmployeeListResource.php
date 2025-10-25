<?php

namespace App\Http\Resources\Employee;

use App\Enums\Employee\Status;
use App\Enums\Employee\Type;
use App\Enums\Gender;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $status = Status::ACTIVE;

        if ($this->leaving_date->value && $this->leaving_date->value < today()->toDateString()) {
            $status = Status::INACTIVE;
        }

        $birthDate = \Cal::date($this->birth_date);
        $startDate = \Cal::date($this->start_date);
        $endDate = \Cal::date($this->end_date);

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'type' => Type::getDetail($this->type),
            'team_name' => $this->team_name ?? '-',
            'other_team_name' => $this->other_team_name ?? '-',
            'other_team_member' => (bool) $this->getMeta('other_team_member'),
            'name' => $this->name,
            'employment_status' => $this->employment_status_name ?? '-',
            'department' => $this->department_name ?? '-',
            'designation' => $this->designation_name ?? '-',
            'employment_status_uuid' => $this->employment_status_uuid,
            'department_uuid' => $this->department_uuid,
            'designation_uuid' => $this->designation_uuid,
            'is_default' => $this->is_default,
            'gender' => Gender::getDetail($this->gender),
            'photo' => $this->photo_url,
            'self' => $this->user_id == auth()->id() ? true : false,
            'birth_date' => $birthDate,
            'age' => $birthDate->age,
            'joining_date' => $this->joining_date,
            'leaving_date' => $this->leaving_date,
            'period' => $this->period,
            'status' => Status::getDetail($status),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_uuid' => $this->user_uuid,
            'created_at' => \Cal::dateTime($this->created_at),
        ];
    }
}
