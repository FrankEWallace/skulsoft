<?php

namespace App\Policies\Employee;

use App\Concerns\SubordinateAccess;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization, SubordinateAccess;

    private function validateTeam(User $user, Employee $employee)
    {
        return $employee->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['employee:read', 'employee:create', 'employee:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('employee:read');
    }

    /**
     * Determine whether the user can view summary of any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewSummary(User $user)
    {
        return $user->can('employee:summary');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Employee $employee)
    {
        if (! $user->can('employee:read')) {
            return false;
        }

        if (! $this->validateTeam($user, $employee)) {
            return false;
        }

        if ($employee?->user_id == auth()->id()) {
            return true;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('employee:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Employee $employee)
    {
        if (! $user->can('employee:edit')) {
            return false;
        }

        if (! $this->validateTeam($user, $employee)) {
            return false;
        }

        if ($employee->user_id == auth()->id()) {
            return false;
        }

        return true;
    }

    public function bulkUpdate(User $user)
    {
        return $user->can('employee:edit');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function editRequest(User $user, Employee $employee)
    {
        return config('config.employee.allow_employee_to_submit_contact_edit_request', false);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function selfService(User $user, Employee $employee)
    {
        if (! $this->validateTeam($user, $employee)) {
            return false;
        }

        if ($user->id != $employee->user_id) {
            if ($user->can('employee-record:manage')) {
                return true;
            }

            return $this->update($user, $employee);
        }

        if (! $user->can('employee:self-service')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function selfServiceAction(User $user, Employee $employee)
    {
        if (! $user->can('employee:self-service-action')) {
            return false;
        }

        if (! $this->validateTeam($user, $employee)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can fetch employment record
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function fetchEmploymentRecord(User $user, Employee $employee)
    {
        if ($employee->user_id == $user->id) {
            return true;
        }

        if (! $user->can('employment-record:manage')) {
            return false;
        }

        if (! $this->validateTeam($user, $employee)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage employee record
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manageEmployeeRecord(User $user, Employee $employee)
    {
        if (! $user->can('employee:edit') && ! $user->can('employee-record:manage')) {
            return false;
        }

        if (! $this->validateTeam($user, $employee)) {
            return false;
        }

        if ($employee->is_default) {
            return false;
        }

        if ($employee->user_id == auth()->id()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage employment record
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manageEmploymentRecord(User $user, Employee $employee)
    {
        if (! $user->can('employment-record:manage')) {
            return false;
        }

        if (! $this->validateTeam($user, $employee)) {
            return false;
        }

        if ($employee->is_default) {
            return false;
        }

        if ($employee->user_id == auth()->id()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Employee $employee)
    {
        if (! $user->can('employee:delete')) {
            return false;
        }
        if (! $this->validateTeam($user, $employee)) {
            return false;
        }

        if ($employee->is_default) {
            return false;
        }

        if ($employee->user_id == auth()->id()) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Employee $employee)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Employee $employee)
    {
        //
    }
}
