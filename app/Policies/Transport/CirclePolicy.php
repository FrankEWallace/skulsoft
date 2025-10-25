<?php

namespace App\Policies\Transport;

use App\Models\Transport\Circle;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CirclePolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Circle $circle)
    {
        return $circle->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['transport-circle:create', 'transport-circle:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('transport-circle:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Circle $circle)
    {
        if (! $this->validateTeam($user, $circle)) {
            return false;
        }

        return $user->can('transport-circle:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('transport-circle:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Circle $circle)
    {
        if (! $this->validateTeam($user, $circle)) {
            return false;
        }

        return $user->can('transport-circle:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Circle $circle)
    {
        if (! $this->validateTeam($user, $circle)) {
            return false;
        }

        return $user->can('transport-circle:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Circle $circle)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Circle $circle)
    {
        //
    }
}
