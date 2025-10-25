<?php

namespace App\Policies\Finance;

use App\Models\Finance\FeeStructure;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeeStructurePolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, FeeStructure $feeStructure)
    {
        return $feeStructure->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['fee-structure:create', 'fee-structure:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('fee-structure:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FeeStructure $feeStructure)
    {
        if (! $this->validateTeam($user, $feeStructure)) {
            return false;
        }

        return $user->can('fee-structure:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('fee-structure:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FeeStructure $feeStructure)
    {
        if (! $this->validateTeam($user, $feeStructure)) {
            return false;
        }

        return $user->can('fee-structure:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FeeStructure $feeStructure)
    {
        if (! $this->validateTeam($user, $feeStructure)) {
            return false;
        }

        return $user->can('fee-structure:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, FeeStructure $feeStructure)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, FeeStructure $feeStructure)
    {
        //
    }
}
