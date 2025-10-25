<?php

namespace App\Http\Resources\Communication;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Http\Resources\AudienceResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EmailResource extends JsonResource
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

        $inclusion = Arr::get($this->lists, 'inclusion');
        $exclusion = Arr::get($this->lists, 'exclusion');

        $inclusion = implode("\n", $inclusion);
        $exclusion = implode("\n", $exclusion);

        return [
            'uuid' => $this->uuid,
            'subject' => $this->subject,
            'subject_excerpt' => Str::limit($this->subject, 100),
            'inclusion' => $inclusion,
            'exclusion' => $exclusion,
            'inclusion_list' => Arr::get($this->lists, 'inclusion'),
            'exclusion_list' => Arr::get($this->lists, 'exclusion'),
            'content' => $this->content,
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'recipient_count' => $this->getMeta('recipient_count'),
            'audience_types' => $audienceTypes,
            'student_audience_type' => StudentAudienceType::getDetail($studentAudienceType),
            'employee_audience_type' => EmployeeAudienceType::getDetail($employeeAudienceType),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
