<?php

namespace App\Policies\Reception;

use App\Models\Reception\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Complaint $complaint)
    {
        return $complaint->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('complaint:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Complaint $complaint)
    {
        if (! $this->validateTeam($user, $complaint)) {
            return false;
        }

        return $user->can('complaint:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('complaint:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Complaint $complaint)
    {
        if (! $this->validateTeam($user, $complaint)) {
            return false;
        }

        return $user->can('complaint:edit');
    }

    /**
     * Determine whether the user can take action on the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function action(User $user, Complaint $complaint)
    {
        if (! $this->validateTeam($user, $complaint)) {
            return false;
        }

        if (! $user->can('complaint:action')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Complaint $complaint)
    {
        if (! $this->validateTeam($user, $complaint)) {
            return false;
        }

        return $user->can('complaint:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Complaint $complaint)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Complaint $complaint)
    {
        //
    }
}
