<?php

namespace App\Policies\Transport\Vehicle;

use App\Models\Transport\Vehicle\TravelRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelRecordPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, TravelRecord $vehicleTravelRecord)
    {
        return $vehicleTravelRecord->vehicle->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['vehicle:create', 'vehicle:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('vehicle:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, TravelRecord $vehicleTravelRecord)
    {
        if (! $this->validateTeam($user, $vehicleTravelRecord)) {
            return false;
        }

        return $user->can('vehicle:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('vehicle:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, TravelRecord $vehicleTravelRecord)
    {
        if (! $this->validateTeam($user, $vehicleTravelRecord)) {
            return false;
        }

        return $user->can('vehicle:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, TravelRecord $vehicleTravelRecord)
    {
        if (! $this->validateTeam($user, $vehicleTravelRecord)) {
            return false;
        }

        return $user->can('vehicle:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, TravelRecord $vehicleTravelRecord)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, TravelRecord $vehicleTravelRecord)
    {
        //
    }
}
