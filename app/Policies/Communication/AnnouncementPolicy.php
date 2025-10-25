<?php

namespace App\Policies\Communication;

use App\Models\Communication\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, Announcement $announcement)
    {
        return $announcement->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['announcement:create', 'announcement:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('announcement:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Announcement $announcement)
    {
        if (! $this->validatePeriod($user, $announcement)) {
            return false;
        }

        return $user->can('announcement:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('announcement:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Announcement $announcement)
    {
        if (! $this->validatePeriod($user, $announcement)) {
            return false;
        }

        return $user->can('announcement:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Announcement $announcement)
    {
        if (! $this->validatePeriod($user, $announcement)) {
            return false;
        }

        return $user->can('announcement:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Announcement $announcement)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Announcement $announcement)
    {
        //
    }
}
