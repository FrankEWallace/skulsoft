<?php

namespace App\Http\Resources\Transport\Report;

use App\Enums\Gender;
use App\Http\Resources\Transport\RouteResource;
use App\Http\Resources\Transport\StoppageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteWiseStudentListResource extends JsonResource
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
            'name' => $this->model->contact->name,
            'father_name' => $this->model->contact->father_name,
            'code_number' => $this->model->admission->code_number,
            'batch_name' => $this->model->batch->name,
            'course_name' => $this->model->batch->course->name,
            'gender' => Gender::getDetail($this->model->contact->gender),
            'contact_number' => $this->model->contact->contact_number,
            'stoppage' => StoppageResource::make($this->stoppage),
            'route' => RouteResource::make($this->route),
        ];
    }
}
