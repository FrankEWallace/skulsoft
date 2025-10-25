<?php

namespace App\Policies\Resource;

use App\Models\Resource\Diary;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiaryPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, Diary $diary)
    {
        return $diary->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['student-diary:create', 'student-diary:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('student-diary:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Diary $diary)
    {
        if (! $this->validatePeriod($user, $diary)) {
            return false;
        }

        return $user->can('student-diary:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('student-diary:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Diary $diary)
    {
        if (! $this->validatePeriod($user, $diary)) {
            return false;
        }

        if (! $user->can('student-diary:edit')) {
            return false;
        }

        if (config('config.resource.allow_edit_diary_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $diary->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Diary $diary)
    {
        if (! $this->validatePeriod($user, $diary)) {
            return false;
        }

        if (! $user->can('student-diary:delete')) {
            return false;
        }

        if (config('config.resource.allow_delete_diary_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $diary->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Diary $diary)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Diary $diary)
    {
        //
    }
}
