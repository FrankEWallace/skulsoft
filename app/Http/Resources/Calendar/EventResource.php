<?php

namespace App\Http\Resources\Calendar;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Academic\SessionResource;
use App\Http\Resources\AudienceResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use App\Models\Academic\Period;
use App\Models\Academic\Session;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class EventResource extends JsonResource
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

        $periodDetails = [];
        $sessionDetails = [];
        if ($this->getMeta('for_alumni') && $request->query('show_details')) {
            $periodDetails = PeriodResource::collection(Period::whereIn('uuid', $this->getMeta('periods', []))->get());
            $sessionDetails = SessionResource::collection(Session::whereIn('uuid', $this->getMeta('sessions', []))->get());
        }

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'title' => $this->title,
            'type' => OptionResource::make($this->whenLoaded('type')),
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'is_public' => $this->is_public,
            'for_alumni' => (bool) $this->getMeta('for_alumni'),
            $this->mergeWhen($this->getMeta('for_alumni'), [
                'periods' => $this->getMeta('periods'),
                'sessions' => $this->getMeta('sessions'),
                'period_details' => $periodDetails,
                'session_details' => $sessionDetails,
            ]),
            $this->mergeWhen($this->is_public, [
                'audience_types' => [trans('general.public')],
            ]),
            $this->mergeWhen(! $this->public, [
                'audience_types' => $audienceTypes,
                'student_audience_type' => StudentAudienceType::getDetail($studentAudienceType),
                'employee_audience_type' => EmployeeAudienceType::getDetail($employeeAudienceType),
            ]),
            'start_date' => $this->start_date,
            'start_time' => $this->start_time,
            'end_date' => $this->end_date,
            'end_time' => $this->end_time,
            'incharge' => EventInchargeResource::make($this->whenLoaded('incharge')),
            'incharges' => EventInchargeResource::collection($this->whenLoaded('incharges')),
            'venue' => $this->venue,
            'description' => $this->description,
            'duration' => $this->duration,
            'duration_in_detail' => $this->duration_in_detail,
            'is_pinned' => $this->getMeta('pinned_at') ? true : false,
            'pinned_at' => \Cal::dateTime($this->getMeta('pinned_at')),
            'cover_image' => $this->cover_image,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
