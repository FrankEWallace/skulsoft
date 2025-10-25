<?php

namespace App\Policies\Finance;

use App\Models\Finance\FeeGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeeGroupPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, FeeGroup $feeGroup)
    {
        return $feeGroup->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['fee-group:create', 'fee-group:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('fee-group:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FeeGroup $feeGroup)
    {
        if (! $this->validateTeam($user, $feeGroup)) {
            return false;
        }

        return $user->can('fee-group:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('fee-group:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FeeGroup $feeGroup)
    {
        if (! $this->validateTeam($user, $feeGroup)) {
            return false;
        }

        return $user->can('fee-group:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FeeGroup $feeGroup)
    {
        if (! $this->validateTeam($user, $feeGroup)) {
            return false;
        }

        return $user->can('fee-group:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, FeeGroup $feeGroup)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, FeeGroup $feeGroup)
    {
        //
    }
}
