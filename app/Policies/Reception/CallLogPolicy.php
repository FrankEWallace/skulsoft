<?php

namespace App\Policies\Reception;

use App\Models\Reception\CallLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CallLogPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, CallLog $callLog)
    {
        return $callLog->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('call-log:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, CallLog $callLog)
    {
        if (! $this->validateTeam($user, $callLog)) {
            return false;
        }

        return $user->can('call-log:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('call-log:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, CallLog $callLog)
    {
        if (! $this->validateTeam($user, $callLog)) {
            return false;
        }

        return $user->can('call-log:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, CallLog $callLog)
    {
        if (! $this->validateTeam($user, $callLog)) {
            return false;
        }

        return $user->can('call-log:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, CallLog $callLog)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, CallLog $callLog)
    {
        //
    }
}
