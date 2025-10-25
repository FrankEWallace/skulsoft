<?php

namespace App\Http\Resources\Employee;

use App\Enums\Employee\Type;
use App\Helpers\CalHelper;
use App\Http\Resources\ContactResource;
use App\Http\Resources\TagResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $self = $this->user_id == auth()->id() ? true : false;

        $selfService = false;
        if ($self && auth()->user()->can('employee:self-service')) {
            $selfService = true;
        }

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'name' => $this->name,
            'type' => Type::getDetail($this->type),
            'is_default' => $this->is_default,
            'self' => $self,
            'self_service' => $selfService,
            'contact' => ContactResource::make($this->whenLoaded('contact')),
            'last_record' => [
                'start_date' => \Cal::date($this->start_date),
                'end_date' => \Cal::date($this->end_date),
                'period' => CalHelper::getPeriod($this->start_date, $this->end_date),
                'duration' => CalHelper::getDuration($this->start_date, $this->end_date, 'day'),
                'department' => ['name' => $this->department_name, 'uuid' => $this->department_uuid],
                'designation' => ['name' => $this->designation_name, 'uuid' => $this->designation_uuid],
                'employment_status' => ['name' => $this->employment_status_name, 'uuid' => $this->employment_status_uuid],
            ],
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'tag_summary' => $this->showTags(),
            $this->mergeWhen($this->whenLoaded('groups'), [
                'groups' => $this->groups->map(function ($group) {
                    return [
                        'uuid' => $group->modelGroup->uuid,
                        'name' => $group->modelGroup->name,
                    ];
                }),
            ]),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'joining_date' => $this->joining_date,
            'leaving_date' => $this->leaving_date,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
