<?php

namespace App\Policies\Reception;

use App\Models\Reception\Correspondence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CorrespondencePolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Correspondence $correspondence)
    {
        return $correspondence->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('correspondence:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Correspondence $correspondence)
    {
        if (! $this->validateTeam($user, $correspondence)) {
            return false;
        }

        return $user->can('correspondence:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('correspondence:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Correspondence $correspondence)
    {
        if (! $this->validateTeam($user, $correspondence)) {
            return false;
        }

        return $user->can('correspondence:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Correspondence $correspondence)
    {
        if (! $this->validateTeam($user, $correspondence)) {
            return false;
        }

        return $user->can('correspondence:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Correspondence $correspondence)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Correspondence $correspondence)
    {
        //
    }
}
