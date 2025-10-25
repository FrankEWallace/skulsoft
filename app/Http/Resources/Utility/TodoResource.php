<?php

namespace App\Http\Resources\Utility;

use App\Http\Resources\OptionResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'due_date' => $this->due_date,
            'due_time' => $this->due_time,
            'due' => $this->getDue(),
            'is_due_today' => $this->due_date->value == today()->toDateString() ? true : false,
            'is_overdue' => $this->is_overdue,
            $this->mergeWhen($this->is_overdue, [
                'overdue_days' => $this->overdue_days,
                'overdue_days_display' => trans('utility.todo.props.overdue_by', ['day' => $this->overdue_days]),
            ]),
            'completed_at' => $this->when($this->completed_at->value, $this->completed_at),
            'is_archived' => $this->archived_at->value ? true : false,
            'archived_at' => $this->archived_at,
            'description' => $this->description,
            'status' => $this->getStatus($request),
            'list' => OptionResource::make($this->whenLoaded('list')),
            'user' => UserResource::make($this->whenLoaded('user')),
            $this->mergeWhen($request->has_custom_fields, [
                'custom_fields' => $this->getCustomFieldsValues(),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getDue()
    {
        if (empty($this->due_time->value)) {
            return $this->due_date;
        }

        return \Cal::dateTime($this->due_date->value.' '.$this->due_time->value);
    }

    private function getStatus($request)
    {
        if ($request->export) {
            return $this->completed_at->value ? trans('utility.todo.completed') : trans('utility.todo.incomplete');
        }

        return $this->completed_at->value ? true : false;
    }
}
