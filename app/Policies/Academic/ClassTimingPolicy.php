<?php

namespace App\Policies\Academic;

use App\Models\Academic\ClassTiming;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassTimingPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, ClassTiming $classTiming)
    {
        return $classTiming->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['class-timing:create', 'class-timing:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('class-timing:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ClassTiming $classTiming)
    {
        if (! $this->validatePeriod($user, $classTiming)) {
            return false;
        }

        return $user->can('class-timing:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('class-timing:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ClassTiming $classTiming)
    {
        if (! $this->validatePeriod($user, $classTiming)) {
            return false;
        }

        return $user->can('class-timing:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ClassTiming $classTiming)
    {
        if (! $this->validatePeriod($user, $classTiming)) {
            return false;
        }

        return $user->can('class-timing:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ClassTiming $classTiming)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ClassTiming $classTiming)
    {
        //
    }
}
