<?php

namespace App\Policies\Transport;

use App\Models\Transport\Fee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeePolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Fee $fee)
    {
        return $fee->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['transport-fee:create', 'transport-fee:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('transport-fee:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Fee $fee)
    {
        if (! $this->validateTeam($user, $fee)) {
            return false;
        }

        return $user->can('transport-fee:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('transport-fee:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Fee $fee)
    {
        if (! $this->validateTeam($user, $fee)) {
            return false;
        }

        return $user->can('transport-fee:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Fee $fee)
    {
        if (! $this->validateTeam($user, $fee)) {
            return false;
        }

        return $user->can('transport-fee:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Fee $fee)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Fee $fee)
    {
        //
    }
}
