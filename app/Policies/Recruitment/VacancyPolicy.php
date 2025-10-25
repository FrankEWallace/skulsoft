<?php

namespace App\Policies\Recruitment;

use App\Models\Recruitment\Vacancy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VacancyPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Vacancy $vacancy)
    {
        return $vacancy->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('job-vacancy:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Vacancy $vacancy)
    {
        if (! $this->validateTeam($user, $vacancy)) {
            return false;
        }

        return $user->can('job-vacancy:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('job-vacancy:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Vacancy $vacancy)
    {
        if (! $this->validateTeam($user, $vacancy)) {
            return false;
        }

        return $user->can('job-vacancy:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Vacancy $vacancy)
    {
        if (! $this->validateTeam($user, $vacancy)) {
            return false;
        }

        return $user->can('job-vacancy:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Vacancy $vacancy)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Vacancy $vacancy)
    {
        //
    }
}
