<?php

namespace App\Policies\Resource;

use App\Models\Resource\Syllabus;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SyllabusPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, Syllabus $syllabus)
    {
        return $syllabus->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['syllabus:create', 'syllabus:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('syllabus:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Syllabus $syllabus)
    {
        if (! $this->validatePeriod($user, $syllabus)) {
            return false;
        }

        return $user->can('syllabus:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('syllabus:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Syllabus $syllabus)
    {
        if (! $this->validatePeriod($user, $syllabus)) {
            return false;
        }

        if (! $user->can('syllabus:edit')) {
            return false;
        }

        if (config('config.resource.allow_edit_syllabus_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $syllabus->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Syllabus $syllabus)
    {
        if (! $this->validatePeriod($user, $syllabus)) {
            return false;
        }

        if (! $user->can('syllabus:delete')) {
            return false;
        }

        if (config('config.resource.allow_delete_syllabus_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $syllabus->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Syllabus $syllabus)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Syllabus $syllabus)
    {
        //
    }
}
