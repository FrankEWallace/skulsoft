<?php

namespace App\Policies\Resource;

use App\Models\Resource\OnlineClass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OnlineClassPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, OnlineClass $onlineClass)
    {
        return $onlineClass->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['online-class:create', 'online-class:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('online-class:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, OnlineClass $onlineClass)
    {
        // already handled in model
        // if ($this->validatePeriod($user, $onlineClass)) {
        //     return false;
        // }

        return $user->can('online-class:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('online-class:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, OnlineClass $onlineClass)
    {
        if (! $this->validatePeriod($user, $onlineClass)) {
            return false;
        }

        if (! $user->can('online-class:edit')) {
            return false;
        }

        if (config('config.resource.allow_edit_online_class_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $onlineClass->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, OnlineClass $onlineClass)
    {
        if (! $this->validatePeriod($user, $onlineClass)) {
            return false;
        }

        if (! $user->can('online-class:delete')) {
            return false;
        }

        if (config('config.resource.allow_delete_online_class_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $onlineClass->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, OnlineClass $onlineClass)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, OnlineClass $onlineClass)
    {
        //
    }
}
