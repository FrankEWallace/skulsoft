<?php

namespace App\Policies\Finance;

use App\Models\Finance\FeeHead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeeHeadPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, FeeHead $feeHead)
    {
        return $feeHead->group->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['fee-head:create', 'fee-head:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('fee-head:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FeeHead $feeHead)
    {
        if (! $this->validateTeam($user, $feeHead)) {
            return false;
        }

        return $user->can('fee-head:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('fee-head:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FeeHead $feeHead)
    {
        if (! $this->validateTeam($user, $feeHead)) {
            return false;
        }

        return $user->can('fee-head:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FeeHead $feeHead)
    {
        if (! $this->validateTeam($user, $feeHead)) {
            return false;
        }

        return $user->can('fee-head:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, FeeHead $feeHead)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, FeeHead $feeHead)
    {
        //
    }
}
