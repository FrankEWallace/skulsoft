<?php

namespace App\Policies\Employee\Payroll;

use App\Models\Employee\Payroll\SalaryTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalaryTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can get pre-requisite.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->hasAnyPermission(['salary-template:read', 'salary-template:create', 'salary-template:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('salary-template:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SalaryTemplate $salaryTemplate)
    {
        return $user->can('salary-template:read') && $salaryTemplate->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('salary-template:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SalaryTemplate $salaryTemplate)
    {
        return $user->can('salary-template:edit') && $salaryTemplate->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SalaryTemplate $salaryTemplate)
    {
        return $user->can('salary-template:delete') && $salaryTemplate->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, SalaryTemplate $salaryTemplate)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, SalaryTemplate $salaryTemplate)
    {
        //
    }
}
