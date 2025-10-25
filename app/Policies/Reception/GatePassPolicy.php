<?php

namespace App\Policies\Reception;

use App\Models\Reception\GatePass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GatePassPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, GatePass $gatePass)
    {
        return $gatePass->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('gate-pass:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, GatePass $gatePass)
    {
        if (! $this->validateTeam($user, $gatePass)) {
            return false;
        }

        return $user->can('gate-pass:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('gate-pass:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, GatePass $gatePass)
    {
        if (! $this->validateTeam($user, $gatePass)) {
            return false;
        }

        return $user->can('gate-pass:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, GatePass $gatePass)
    {
        if (! $this->validateTeam($user, $gatePass)) {
            return false;
        }

        return $user->can('gate-pass:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, GatePass $gatePass)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, GatePass $gatePass)
    {
        //
    }
}
