<?php

namespace App\Http\Resources\Form;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Form\Status;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Http\Resources\AudienceResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FormResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $studentAudienceType = Arr::get($this->audience, 'student_type');
        $employeeAudienceType = Arr::get($this->audience, 'employee_type');

        $audienceTypes = [];
        if ($studentAudienceType) {
            $audienceTypes[] = StudentAudienceType::getLabel($studentAudienceType);
        }

        if ($employeeAudienceType) {
            $audienceTypes[] = EmployeeAudienceType::getLabel($employeeAudienceType);
        }

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'due_date' => $this->due_date,
            'published_at' => $this->published_at,
            'submissions_count' => $this->submissions_count,
            'submitted_at' => \Cal::dateTime($this->submitted_at),
            'is_expired' => empty($this->submitted_at) && $this->due_date->value < today()->toDateString() ? true : false,
            'summary' => $this->summary,
            'excerpt' => Str::summary($this->summary),
            'description' => $this->description,
            'status' => Status::getDetail($this->status),
            'audience_types' => $audienceTypes,
            'student_audience_type' => StudentAudienceType::getDetail($studentAudienceType),
            'employee_audience_type' => EmployeeAudienceType::getDetail($employeeAudienceType),
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'fields' => FieldResource::collection($this->whenLoaded('fields')),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'is_editable' => $this->is_editable,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
