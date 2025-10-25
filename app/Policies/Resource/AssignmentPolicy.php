<?php

namespace App\Policies\Resource;

use App\Models\Resource\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, Assignment $assignment)
    {
        return $assignment->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['assignment:create', 'assignment:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('assignment:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Assignment $assignment)
    {
        // already handled in model
        // if ($this->validatePeriod($user, $assignment)) {
        //     return false;
        // }

        return $user->can('assignment:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('assignment:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Assignment $assignment)
    {
        if (! $this->validatePeriod($user, $assignment)) {
            return false;
        }

        if (! $user->can('assignment:edit')) {
            return false;
        }

        if (config('config.resource.allow_edit_assignment_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $assignment->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Assignment $assignment)
    {
        if (! $this->validatePeriod($user, $assignment)) {
            return false;
        }

        if (! $user->can('assignment:delete')) {
            return false;
        }

        if (config('config.resource.allow_delete_assignment_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $assignment->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Assignment $assignment)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Assignment $assignment)
    {
        //
    }
}
