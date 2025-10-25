<?php

namespace App\Http\Resources\Approval;

use App\Http\Resources\Employee\EmployeeSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
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
            'is_other_team_member' => (bool) $this->getConfig('is_other_team_member', false),
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'position' => $this->position,
            'actions' => collect($this->getConfig('actions', []) ?? [])->map(function ($action) {
                return [
                    'label' => trans("approval.actions.{$action}"),
                    'value' => $action,
                ];
            }),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
