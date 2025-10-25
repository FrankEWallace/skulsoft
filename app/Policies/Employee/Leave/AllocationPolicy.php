<?php

namespace App\Policies\Employee\Leave;

use App\Concerns\SubordinateAccess;
use App\Models\Employee\Leave\Allocation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AllocationPolicy
{
    use HandlesAuthorization, SubordinateAccess;

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('leave-allocation:create') || $user->can('leave-allocation:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('leave-allocation:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Allocation $leaveAllocation)
    {
        if (! $user->can('leave-allocation:read')) {
            return false;
        }

        if ($leaveAllocation->employee->user_id == $user->id) {
            return true;
        }

        if ($leaveAllocation?->employee?->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleEmployee($leaveAllocation->employee);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('leave-allocation:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Allocation $leaveAllocation)
    {
        if (! $user->can('leave-allocation:edit')) {
            return false;
        }

        if ($leaveAllocation?->employee?->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleEmployee($leaveAllocation->employee);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Allocation $leaveAllocation)
    {
        if (! $user->can('leave-allocation:delete')) {
            return false;
        }

        if ($leaveAllocation?->employee?->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleEmployee($leaveAllocation->employee);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Allocation $leaveAllocation)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Allocation $leaveAllocation)
    {
        //
    }
}
