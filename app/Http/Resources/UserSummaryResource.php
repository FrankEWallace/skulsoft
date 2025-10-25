<?php

namespace App\Http\Resources;

use App\Http\Resources\Team\RoleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSummaryResource extends JsonResource
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
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'current_period_id' => $this->current_period_id,
            'current_team_id' => $this->current_team_id,
            'profile' => [
                'name' => $this->name,
                'avatar' => $this->avatar,
            ],
        ];
    }
}
