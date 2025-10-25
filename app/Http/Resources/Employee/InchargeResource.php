<?php

namespace App\Http\Resources\Employee;

use App\Helpers\CalHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class InchargeResource extends JsonResource
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
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'name' => $this->model->name,
            'detail' => $this->detail?->name,
            'type' => match ($this->model_type) {
                'AcademicDepartment' => trans('academic.department_incharge.department_incharge'),
                'Program' => trans('academic.program_incharge.program_incharge'),
                'Division' => trans('academic.division_incharge.division_incharge'),
                'Course' => trans('academic.course_incharge.course_incharge'),
                'Branch' => trans('academic.branch_incharge.branch_incharge'),
                'Subject' => trans('academic.subject_incharge.subject_incharge'),
                default => trans('general.detail'),
            },
            'period' => CalHelper::getPeriod($this->start_date->value, $this->end_date->value),
            'duration' => CalHelper::getDuration($this->start_date->value, $this->end_date->value),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
