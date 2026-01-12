<?php

namespace App\Policies\Academic;

use App\Domain\Academic\Models\Timetable;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimetablePolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, Timetable $timetable)
    {
        return $timetable->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['timetable:create', 'timetable:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('timetable:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Timetable $timetable)
    {
        if (! $this->validatePeriod($user, $timetable)) {
            return false;
        }

        return $user->can('timetable:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('timetable:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Timetable $timetable)
    {
        if (! $this->validatePeriod($user, $timetable)) {
            return false;
        }

        return $user->can('timetable:edit');
    }

    public function exportTeacherTimetable(User $user)
    {
        return ! $user->hasAnyRole(['student', 'guardian']);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Timetable $timetable)
    {
        if (! $this->validatePeriod($user, $timetable)) {
            return false;
        }

        return $user->can('timetable:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Timetable $timetable)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Timetable $timetable)
    {
        //
    }
}
