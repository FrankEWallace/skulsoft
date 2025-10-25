<?php

namespace App\Http\Resources\Resource;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Http\Resources\AudienceResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DownloadResource extends JsonResource
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

        $audienceTypes = [];
        if ($studentAudienceType) {
            $audienceTypes[] = StudentAudienceType::getLabel($studentAudienceType);
        }

        $employeeAudienceType = Arr::get($this->audience, 'employee_type');

        if ($employeeAudienceType) {
            $audienceTypes[] = EmployeeAudienceType::getLabel($employeeAudienceType);
        }

        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'title_excerpt' => Str::summary($this->title, 100),
            'description' => $this->description,
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'audience_types' => $audienceTypes,
            'student_audience_type' => StudentAudienceType::getDetail($studentAudienceType),
            'employee_audience_type' => EmployeeAudienceType::getDetail($employeeAudienceType),
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'is_public' => $this->is_public,
            'is_editable' => $this->is_editable,
            'is_deletable' => $this->is_deletable,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
