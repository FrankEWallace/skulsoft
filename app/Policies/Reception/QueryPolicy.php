<?php

namespace App\Policies\Reception;

use App\Models\Reception\Query;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QueryPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Query $query)
    {
        return $query->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('query:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Query $query)
    {
        if (! $this->validateTeam($user, $query)) {
            return false;
        }

        return $user->can('query:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    // public function create(User $user)
    // {
    //     return $user->can('query:create');
    // }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    // public function update(User $user, Query $query)
    // {
    //     if (! $this->validateTeam($user, $query)) {
    //         return false;
    //     }

    //     return $user->can('query:edit');
    // }

    /**
     * Determine whether the user can take action on the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function action(User $user, Query $query)
    {
        if (! $this->validateTeam($user, $query)) {
            return false;
        }

        if (! $user->can('query:action')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Query $query)
    {
        if (! $this->validateTeam($user, $query)) {
            return false;
        }

        return $user->can('query:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Query $query)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Query $query)
    {
        //
    }
}
