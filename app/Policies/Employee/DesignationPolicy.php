<?php

namespace App\Policies\Employee;

use App\Concerns\SubordinateAccess;
use App\Models\Employee\Designation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DesignationPolicy
{
    use HandlesAuthorization, SubordinateAccess;

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('designation:create') || $user->can('designation:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('designation:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Designation $designation)
    {
        if (! $user->can('designation:read')) {
            return false;
        }

        if ($designation->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleDesignation($designation);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('designation:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Designation $designation)
    {
        if (! $user->can('designation:edit')) {
            return false;
        }

        if ($designation->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleDesignation($designation);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Designation $designation)
    {
        if (! $user->can('designation:delete')) {
            return false;
        }

        if ($designation->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleDesignation($designation);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Designation $designation)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Designation $designation)
    {
        //
    }
}
