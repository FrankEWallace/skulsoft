<?php

namespace App\Http\Resources\Reception;

use App\Enums\Gender;
use App\Enums\Reception\EnquiryNature;
use App\Enums\Reception\EnquiryStatus;
use App\Http\Resources\Academic\CourseResource;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EnquiryResource extends JsonResource
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
            'nature' => EnquiryNature::getDetail($this->nature ?? 'other'),
            'period' => PeriodResource::make($this->whenLoaded('period')),
            'stage' => OptionResource::make($this->whenLoaded('stage')),
            'type' => OptionResource::make($this->whenLoaded('type')),
            'source' => OptionResource::make($this->whenLoaded('source')),
            'course' => CourseResource::make($this->whenLoaded('course')),
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'contact' => ContactResource::make($this->whenLoaded('contact')),
            'date' => $this->date,
            $this->mergeWhen($this->nature == EnquiryNature::ADMISSION, [
                'name' => $this->contact?->name,
                'contact_number' => $this->contact?->contact_number,
                'email' => $this->contact?->email,
                'gender' => Gender::getDetail($this->contact?->gender),
                'birth_date' => $this->contact?->birth_date,
            ], [
                'name' => $this->name,
                'email' => $this->email,
                'contact_number' => $this->contact_number,
            ]),
            'status' => EnquiryStatus::getDetail($this->status),
            'follow_ups' => EnquiryFollowUpResource::collection($this->whenLoaded('followUps')),
            'description' => $this->description,
            'remarks' => $this->remarks,
            $this->mergeWhen($request->has_custom_fields, [
                'custom_fields' => $this->getCustomFieldsValues(),
            ]),
            'is_editable' => $this->is_editable,
            'is_converted' => $this->is_converted,
            $this->mergeWhen($this->is_converted, [
                'registration' => [
                    'uuid' => $this->getMeta('registration_uuid'),
                ],
            ]),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
