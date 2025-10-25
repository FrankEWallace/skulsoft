<?php

namespace App\Policies\Finance;

use App\Models\Finance\LedgerType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LedgerTypePolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, LedgerType $ledgerType)
    {
        return $ledgerType->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['ledger-type:create', 'ledger-type:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('ledger-type:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, LedgerType $ledgerType)
    {
        if (! $this->validateTeam($user, $ledgerType)) {
            return false;
        }

        return $user->can('ledger-type:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('ledger-type:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, LedgerType $ledgerType)
    {
        if (! $this->validateTeam($user, $ledgerType)) {
            return false;
        }

        return $user->can('ledger-type:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, LedgerType $ledgerType)
    {
        if (! $this->validateTeam($user, $ledgerType)) {
            return false;
        }

        return $user->can('ledger-type:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, LedgerType $ledgerType)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, LedgerType $ledgerType)
    {
        //
    }
}
