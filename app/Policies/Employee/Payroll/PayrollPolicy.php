<?php

namespace App\Policies\Employee\Payroll;

use App\Concerns\SubordinateAccess;
use App\Models\Employee\Payroll\Payroll;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPolicy
{
    use HandlesAuthorization, SubordinateAccess;

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('payroll:read') || $user->can('payroll:create') || $user->can('payroll:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('payroll:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Payroll $payroll)
    {
        if (! $user->can('payroll:read')) {
            return false;
        }

        if ($payroll->employee->user_id == $user->id) {
            return true;
        }

        if ($payroll?->employee?->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleEmployee($payroll->employee);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('payroll:create');
    }

    public function process(User $user)
    {
        return $user->can('payroll:process');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Payroll $payroll)
    {
        if (! $user->can('payroll:edit')) {
            return false;
        }

        if ($payroll?->employee?->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleEmployee($payroll->employee);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Payroll $payroll)
    {
        if (! $user->can('payroll:delete')) {
            return false;
        }

        if ($payroll?->employee?->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleEmployee($payroll->employee);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Payroll $payroll)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Payroll $payroll)
    {
        //
    }
}
