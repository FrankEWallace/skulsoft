<?php

namespace App\Http\Resources\Reception;

use App\Enums\Reception\ComplaintStatus;
use App\Http\Resources\Calendar\EventInchargeResource;
use App\Http\Resources\Employee\EmployeeBasicResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\StudentSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ComplaintResource extends JsonResource
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
            'student' => StudentSummaryResource::make($this->whenLoaded('model')),
            'employee' => EmployeeBasicResource::make($this->whenLoaded('employee')),
            'incharge' => EventInchargeResource::make($this->whenLoaded('incharge')),
            'incharges' => EventInchargeResource::collection($this->whenLoaded('incharges')),
            'type' => OptionResource::make($this->whenLoaded('type')),
            'logs' => ComplaintLogResource::collection($this->whenLoaded('logs')),
            'subject' => $this->subject,
            'subject_excerpt' => Str::summary($this->subject, 100),
            'date' => $this->date,
            'time' => $this->time,
            'status' => ComplaintStatus::getDetail($this->status),
            'complainant_name' => Arr::get($this->complainant, 'name'),
            'complainant_contact_number' => Arr::get($this->complainant, 'contact_number'),
            'complainant_address' => Arr::get($this->complainant, 'address'),
            'description' => $this->description,
            'action' => $this->action,
            'is_editable' => $this->is_editable,
            'is_online' => (bool) $this->getMeta('is_online'),
            'resolved_at' => $this->resolved_at,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
