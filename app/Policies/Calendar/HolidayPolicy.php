<?php

namespace App\Policies\Calendar;

use App\Models\Calendar\Holiday;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HolidayPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Holiday $holiday)
    {
        return $holiday->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('holiday:create') || $user->can('holiday:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('holiday:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Holiday $holiday)
    {
        if (! $this->validateTeam($user, $holiday)) {
            return false;
        }

        return $user->can('holiday:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('holiday:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Holiday $holiday)
    {
        if (! $this->validateTeam($user, $holiday)) {
            return false;
        }

        return $user->can('holiday:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Holiday $holiday)
    {
        if (! $this->validateTeam($user, $holiday)) {
            return false;
        }

        return $user->can('holiday:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Holiday $holiday)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Holiday $holiday)
    {
        //
    }
}
