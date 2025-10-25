<?php

namespace App\Policies\Mess;

use App\Models\Mess\MealLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MealLogPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, MealLog $mealLog)
    {
        return $mealLog->meal->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('meal-log:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, MealLog $mealLog)
    {
        if (! $this->validateTeam($user, $mealLog)) {
            return false;
        }

        return $user->can('meal-log:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('meal-log:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, MealLog $mealLog)
    {
        if (! $this->validateTeam($user, $mealLog)) {
            return false;
        }

        return $user->can('meal-log:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, MealLog $mealLog)
    {
        if (! $this->validateTeam($user, $mealLog)) {
            return false;
        }

        return $user->can('meal-log:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, MealLog $mealLog)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, MealLog $mealLog)
    {
        //
    }
}
