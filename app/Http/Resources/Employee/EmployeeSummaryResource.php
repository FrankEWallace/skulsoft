<?php

namespace App\Http\Resources\Employee;

use App\Enums\Employee\Type;
use App\Enums\Gender;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSummaryResource extends JsonResource
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
            'code_number' => $this->code_number,
            'team_name' => $this->team_name ?? '-',
            'team_code' => $this->team_code ?? '-',
            'is_other_team_member' => $this->team_id != auth()->user()?->current_team_id ? true : false,
            'name' => $this->name,
            'type' => Type::getDetail($this->type),
            'birth_date' => $this->birth_date,
            'photo' => $this->photo_url,
            'gender' => Gender::getDetail($this->gender),
            'employment_status' => $this->employment_status_name ?? '-',
            'department' => $this->department_name ?? '-',
            'designation' => $this->designation_name ?? '-',
            'self' => $this->user_id == auth()->id() ? true : false,
            'joining_date' => $this->joining_date,
            'leaving_date' => $this->leaving_date,
            'created_at' => \Cal::dateTime($this->created_at),
        ];
    }
}
