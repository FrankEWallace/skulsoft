<?php

namespace App\Policies\Exam;

use App\Models\Exam\Schedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchedulePolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, Schedule $schedule)
    {
        return $schedule->exam->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('exam-schedule:read') || $user->can('exam-schedule:create') || $user->can('exam-schedule:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('exam-schedule:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Schedule $schedule)
    {
        if (! $this->validatePeriod($user, $schedule)) {
            return false;
        }

        return $user->can('exam-schedule:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('exam-schedule:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Schedule $schedule)
    {
        if (! $this->validatePeriod($user, $schedule)) {
            return false;
        }

        return $user->can('exam-schedule:edit');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function confirmForm(User $user, Schedule $schedule)
    {
        if (! $this->validatePeriod($user, $schedule)) {
            return false;
        }

        if (! $user->hasRole('student')) {
            return false;
        }

        if (! $schedule->has_form) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function submitForm(User $user, Schedule $schedule)
    {
        if (! $this->validatePeriod($user, $schedule)) {
            return false;
        }

        if (! $user->hasRole('student')) {
            return false;
        }

        if (! $schedule->has_form) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Schedule $schedule)
    {
        if (! $this->validatePeriod($user, $schedule)) {
            return false;
        }

        return $user->can('exam-schedule:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Schedule $schedule)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Schedule $schedule)
    {
        //
    }
}
