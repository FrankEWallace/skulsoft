<?php

namespace App\Http\Resources;

use App\Enums\UserStatus;
use App\Http\Resources\Team\RoleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'status' => UserStatus::getDetail($this->status),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'profile' => [
                'name' => $this->name,
            ],
            'current_team_id' => $this->current_team_id,
            'current_period_id' => $this->current_period_id,
            'avatar' => $this->avatar,
            'is_super_admin' => $this->when($this->is_default, true),
            $this->mergeWhen($request->boolean('with_direct_permission'), [
                'show_permissions' => true,
                'permissions' => $this->permissions->pluck('name'),
            ]),
            'is_editable' => $this->isEditable() && \Auth::user()->can('user:edit'),
            'is_deletable' => $this->isEditable() && \Auth::user()->can('user:delete'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getRoles()
    {
        $roles = [];
        foreach ($this->getRoleNames() as $role) {
            $roles[] = title_case($role);
        }

        return $roles;
    }
}
