<?php

namespace App\Policies\Finance;

use App\Models\Finance\FeeConcession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeeConcessionPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, FeeConcession $feeConcession)
    {
        return $feeConcession->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['fee-concession:create', 'fee-concession:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('fee-concession:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FeeConcession $feeConcession)
    {
        if (! $this->validateTeam($user, $feeConcession)) {
            return false;
        }

        return $user->can('fee-concession:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('fee-concession:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FeeConcession $feeConcession)
    {
        if (! $this->validateTeam($user, $feeConcession)) {
            return false;
        }

        return $user->can('fee-concession:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FeeConcession $feeConcession)
    {
        if (! $this->validateTeam($user, $feeConcession)) {
            return false;
        }

        return $user->can('fee-concession:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, FeeConcession $feeConcession)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, FeeConcession $feeConcession)
    {
        //
    }
}
