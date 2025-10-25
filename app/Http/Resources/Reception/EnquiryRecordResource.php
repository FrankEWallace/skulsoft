<?php

namespace App\Http\Resources\Reception;

use App\Enums\Gender;
use App\Enums\Reception\EnquiryStatus;
use App\Http\Resources\Academic\CourseResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EnquiryRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isConverted = (bool) $this->getMeta('is_converted');

        return [
            'uuid' => $this->uuid,
            'student_name' => $this->student_name,
            'birth_date' => $this->birth_date,
            'gender' => Gender::getDetail($this->gender),
            'course' => CourseResource::make($this->whenLoaded('course')),
            'contact_number' => $this->contact_number,
            'status' => EnquiryStatus::getDetail($this->status),
            'is_converted' => $isConverted,
            $this->mergeWhen($isConverted, [
                'registration_uuid' => $this->getMeta('registration_uuid'),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
