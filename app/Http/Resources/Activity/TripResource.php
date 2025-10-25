<?php

namespace App\Http\Resources\Activity;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Http\Resources\AudienceResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TripResource extends JsonResource
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
            'title' => $this->title,
            'title_excerpt' => Str::limit($this->title, 50),
            'type' => OptionResource::make($this->whenLoaded('type')),
            $this->mergeWhen($request->show_detail, [
                'participants_count' => $this->participants_count,
                'media_count' => $this->when($this->relationLoaded('media'), $this->media->count()),
            ]),
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'audience_types' => $audienceTypes,
            'student_audience_type' => StudentAudienceType::getDetail($studentAudienceType),
            'employee_audience_type' => EmployeeAudienceType::getDetail($employeeAudienceType),
            'start_date' => $this->start_date,
            'start_time' => $this->start_time,
            'end_date' => $this->end_date,
            'end_time' => $this->end_time,
            'fee' => \Price::from(Arr::get(Arr::first($this->fees), 'amount')),
            'venue' => $this->venue,
            'summary' => $this->summary,
            'itinerary' => $this->itinerary,
            'description' => $this->description,
            'duration' => $this->duration,
            'duration_in_detail' => $this->duration_in_detail,
            'cover_image' => $this->cover_image,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
