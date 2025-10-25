<?php

namespace App\Http\Resources\Communication;

use App\Concerns\HasViewLogs;
use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Http\Resources\AudienceResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AnnouncementResource extends JsonResource
{
    use HasViewLogs;

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
            'code_number' => $this->code_number,
            'title' => $this->title,
            'title_excerpt' => Str::summary($this->title, 100),
            'type' => OptionResource::make($this->whenLoaded('type')),
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'is_public' => $this->is_public,
            $this->mergeWhen($this->is_public, [
                'audience_types' => [trans('general.public')],
            ]),
            $this->mergeWhen(! $this->public, [
                'audience_types' => $audienceTypes,
                'student_audience_type' => StudentAudienceType::getDetail($studentAudienceType),
                'employee_audience_type' => EmployeeAudienceType::getDetail($employeeAudienceType),
            ]),
            'is_pinned' => $this->getMeta('pinned_at') ? true : false,
            'pinned_at' => \Cal::dateTime($this->getMeta('pinned_at')),
            'description' => $this->description,
            'published_at' => $this->published_at,
            $this->mergeWhen(auth()->user()->can('announcement:view-log'), [
                'view_logs' => $this->getViewLogs(),
            ]),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
