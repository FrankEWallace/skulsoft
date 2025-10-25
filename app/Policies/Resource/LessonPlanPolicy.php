<?php

namespace App\Policies\Resource;

use App\Models\Resource\LessonPlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonPlanPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, LessonPlan $lessonPlan)
    {
        return $lessonPlan->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['lesson-plan:create', 'lesson-plan:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('lesson-plan:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, LessonPlan $lessonPlan)
    {
        if (! $this->validatePeriod($user, $lessonPlan)) {
            return false;
        }

        return $user->can('lesson-plan:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('lesson-plan:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, LessonPlan $lessonPlan)
    {
        if (! $this->validatePeriod($user, $lessonPlan)) {
            return false;
        }

        if (! $user->can('lesson-plan:edit')) {
            return false;
        }

        if (config('config.resource.allow_edit_lesson_plan_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $lessonPlan->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, LessonPlan $lessonPlan)
    {
        if (! $this->validatePeriod($user, $lessonPlan)) {
            return false;
        }

        if (! $user->can('lesson-plan:delete')) {
            return false;
        }

        if (config('config.resource.allow_delete_lesson_plan_by_accessible_user')) {
            return true;
        }

        if ($user->hasRole('staff') && $user->id != $lessonPlan->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, LessonPlan $lessonPlan)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, LessonPlan $lessonPlan)
    {
        //
    }
}
