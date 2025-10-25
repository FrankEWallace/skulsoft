<?php

namespace App\Policies\Reception;

use App\Models\Reception\VisitorLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitorLogPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, VisitorLog $visitorLog)
    {
        return $visitorLog->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('visitor-log:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, VisitorLog $visitorLog)
    {
        if (! $this->validateTeam($user, $visitorLog)) {
            return false;
        }

        return $user->can('visitor-log:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('visitor-log:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, VisitorLog $visitorLog)
    {
        if (! $this->validateTeam($user, $visitorLog)) {
            return false;
        }

        return $user->can('visitor-log:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, VisitorLog $visitorLog)
    {
        if (! $this->validateTeam($user, $visitorLog)) {
            return false;
        }

        return $user->can('visitor-log:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, VisitorLog $visitorLog)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, VisitorLog $visitorLog)
    {
        //
    }
}
