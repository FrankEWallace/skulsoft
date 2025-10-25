<?php

namespace App\Policies\Employee\Attendance;

use App\Models\Employee\Attendance\WorkShift;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkShiftPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('work-shift:create') || $user->can('work-shift:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('work-shift:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, WorkShift $workShift)
    {
        return $user->can('work-shift:read') && $workShift->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('work-shift:create');
    }

    /**
     * Determine whether the user can assign models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function assign(User $user)
    {
        return $user->can('work-shift:assign');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, WorkShift $workShift)
    {
        return $user->can('work-shift:edit') && $workShift->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, WorkShift $workShift)
    {
        return $user->can('work-shift:delete') && $workShift->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, WorkShift $workShift)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, WorkShift $workShift)
    {
        //
    }
}
