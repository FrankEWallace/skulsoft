<?php

namespace App\Http\Resources\Employee\Attendance;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'name' => $this->name,
            'employment_status' => $this->employment_status_name ?? '-',
            'department' => $this->department_name ?? '-',
            'designation' => $this->designation_name ?? '-',
            'branch' => $this->branch_name ?? '-',
            'employment_status_uuid' => $this->employment_status_uuid,
            'department_uuid' => $this->department_uuid,
            'designation_uuid' => $this->designation_uuid,
            'branch_uuid' => $this->branch_uuid,
            $this->mergeWhen($this->mark_attendance, [
                'not_available' => $this->not_available,
                'forced_attendance' => $this->forced_attendance,
                'on_leave' => $this->on_leave,
                'is_half_day_leave' => $this->is_half_day_leave,
                $this->mergeWhen($this->on_leave, [
                    'leave_period' => $this->leave_period,
                ]),
                'attendance_type' => $this->attendance_type,
                'time_based_attendance' => $this->time_based_attendance,
                'remarks' => $this->remarks,
            ]),
            $this->mergeWhen($this->list_attendance, [
                'attendances' => $this->attendances,
            ]),
            $this->mergeWhen($this->list_summary, [
                'summary' => $this->summary,
                'additional_summary' => $this->additional_summary,
            ]),
        ];
    }
}
