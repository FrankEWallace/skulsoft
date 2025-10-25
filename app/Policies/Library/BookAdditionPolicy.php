<?php

namespace App\Policies\Library;

use App\Models\Library\BookAddition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookAdditionPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, BookAddition $bookAddition)
    {
        return $bookAddition->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('book-addition:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, BookAddition $bookAddition)
    {
        if (! $this->validateTeam($user, $bookAddition)) {
            return false;
        }

        return $user->can('book-addition:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('book-addition:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, BookAddition $bookAddition)
    {
        if (! $this->validateTeam($user, $bookAddition)) {
            return false;
        }

        return $user->can('book-addition:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, BookAddition $bookAddition)
    {
        if (! $this->validateTeam($user, $bookAddition)) {
            return false;
        }

        return $user->can('book-addition:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, BookAddition $bookAddition)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, BookAddition $bookAddition)
    {
        //
    }
}
