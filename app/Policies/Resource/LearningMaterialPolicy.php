<?php

namespace App\Policies\Resource;

use App\Models\Employee\Employee;
use App\Models\Resource\LearningMaterial;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningMaterialPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, LearningMaterial $learningMaterial)
    {
        return $learningMaterial->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['learning-material:create', 'learning-material:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('learning-material:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, LearningMaterial $learningMaterial)
    {
        // already handled in model
        // if (! $this->validatePeriod($user, $learningMaterial)) {
        //     return false;
        // }

        return $user->can('learning-material:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('learning-material:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, LearningMaterial $learningMaterial)
    {
        if (! $this->validatePeriod($user, $learningMaterial)) {
            return false;
        }

        if (! $user->can('learning-material:edit')) {
            return false;
        }

        if (config('config.resource.allow_edit_learning_material_by_accessible_user')) {
            return true;
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
    public function delete(User $user, LearningMaterial $learningMaterial)
    {
        if (! $this->validatePeriod($user, $learningMaterial)) {
            return false;
        }

        if (! $user->can('learning-material:delete')) {
            return false;
        }

        if (config('config.resource.allow_delete_learning_material_by_accessible_user')) {
            return true;
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
    public function restore(User $user, LearningMaterial $learningMaterial)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, LearningMaterial $learningMaterial)
    {
        //
    }
}
