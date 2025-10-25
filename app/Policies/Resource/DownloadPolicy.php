<?php

namespace App\Policies\Resource;

use App\Models\Employee\Employee;
use App\Models\Resource\Download;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DownloadPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Download $download)
    {
        return $download->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['download:create', 'download:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('download:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Download $download)
    {
        if (! $this->validateTeam($user, $download)) {
            return false;
        }

        return $user->can('download:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('download:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Download $download)
    {
        if (! $this->validateTeam($user, $download)) {
            return false;
        }

        if (! $user->can('download:edit')) {
            return false;
        }

        if ($user->hasRole('staff') && $user->id != Employee::auth()->first()?->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Download $download)
    {
        if (! $this->validateTeam($user, $download)) {
            return false;
        }

        if (! $user->can('download:delete')) {
            return false;
        }

        if ($user->hasRole('staff') && $user->id != Employee::auth()->first()?->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Download $download)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Download $download)
    {
        //
    }
}
