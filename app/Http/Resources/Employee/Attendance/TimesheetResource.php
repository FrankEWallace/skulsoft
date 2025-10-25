<?php

namespace App\Http\Resources\Employee\Attendance;

use App\Enums\Employee\Attendance\TimesheetStatus;
use App\Helpers\CalHelper;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $duration = '';

        if ($this->out_at->value) {
            $duration = Carbon::parse($this->out_at->value)->diff($this->in_at->value)->format('%H:%I:%S');
        }

        return [
            'uuid' => $this->uuid,
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'work_shift' => WorkShiftResource::make($this->whenLoaded('workShift')),
            'date' => $this->date,
            'day' => CalHelper::showDay($this->date->value),
            'in_at' => $this->in_at,
            'in_at_date' => \Cal::date($this->in_at->value),
            'in_at_time' => \Cal::time($this->in_at->value),
            'duration' => $duration,
            'clock_in' => $this->out_at->value ? true : false,
            'clock_out' => $this->out_at->value ? false : true,
            'out_at' => $this->out_at,
            'out_at_date' => \Cal::date($this->out_at->value),
            'out_at_time' => \Cal::time($this->out_at->value),
            'is_manual' => $this->is_manual ? true : false,
            'is_synched' => $this->status ? true : false,
            'status' => TimesheetStatus::getDetail($this->status),
            'is_overnight' => $this->getMeta('is_overnight'),
            'is_holiday' => $this->getMeta('is_holiday'),
            'remarks' => $this->remarks,
            'is_editable' => $this->status ? false : true,
            'is_deletable' => $this->status ? false : true,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
