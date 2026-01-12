<?php

namespace App\Http\Resources;

use App\Domain\Academic\Models\Period;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
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
            'status' => $this->status->value,
            'roles' => $this->user_role,
            'permissions' => $this->user_permission,
            'is_super_admin' => $this->when($this->is_default, true),
            'profile' => [
                'name' => $this->name,
            ],
            'avatar' => $this->avatar,
            'preference' => $this->user_preference,
            'current_team_id' => $this->current_team_id,
            'scope' => $this->getMeta('scope', 'current_team'),
            'scope_detail' => $this->getMeta('scope_detail'),
            $this->mergeWhen($request->refresh_period, [
                'current_period_id' => $this->getCurrentPeriodId(),
            ], [
                'current_period_id' => $this->current_period_id,
            ]),
        ];
    }

    private function getCurrentPeriodId()
    {
        $periods = Period::byTeam()->get();
        $defaultPeriodId = $periods->firstwhere('is_default', true)?->id;

        $currentPeriodId = $this->getPreference('academic.period_id');

        if (! $currentPeriodId) {
            return $defaultPeriodId;
        }

        if (! in_array($currentPeriodId, $periods->pluck('id')->all())) {
            return $defaultPeriodId;
        }

        return $currentPeriodId;
    }
}
