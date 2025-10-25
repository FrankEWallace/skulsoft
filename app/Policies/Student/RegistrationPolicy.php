<?php

namespace App\Policies\Student;

use App\Models\Student\Registration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistrationPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Registration $registration)
    {
        return $registration->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['registration:create', 'registration:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('registration:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Registration $registration)
    {
        if (! $this->validateTeam($user, $registration)) {
            return false;
        }

        return $user->can('registration:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('registration:create');
    }

    /**
     * Determine whether the user can bulk update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function bulkUpdate(User $user)
    {
        return $user->can('registration:edit');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Registration $registration)
    {
        if (! $this->validateTeam($user, $registration)) {
            return false;
        }

        return $user->can('registration:edit');
    }

    /**
     * Determine whether the user can take action on the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function action(User $user, Registration $registration)
    {
        if (! $this->validateTeam($user, $registration)) {
            return false;
        }

        return $user->can('registration:action');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Registration $registration)
    {
        if (! $this->validateTeam($user, $registration)) {
            return false;
        }

        return $user->can('registration:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Registration $registration)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Registration $registration)
    {
        //
    }
}
