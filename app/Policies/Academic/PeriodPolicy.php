<?php

namespace App\Policies\Academic;

use App\Models\Academic\Period;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PeriodPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Period $period)
    {
        return $period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user has same team of the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function validTeam(User $user, Period $period)
    {
        return $period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['period:create', 'period:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('period:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Period $period)
    {
        if (! $this->validateTeam($user, $period)) {
            return false;
        }

        return $user->can('period:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('period:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Period $period)
    {
        if (! $this->validateTeam($user, $period)) {
            return false;
        }

        return $user->can('period:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Period $period)
    {
        if (! $this->validateTeam($user, $period)) {
            return false;
        }

        return $user->can('period:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Period $period)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Period $period)
    {
        //
    }
}
