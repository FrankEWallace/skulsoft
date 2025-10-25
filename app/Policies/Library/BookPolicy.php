<?php

namespace App\Policies\Library;

use App\Models\Library\Book;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Book $book)
    {
        return $book->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('book:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Book $book)
    {
        if (! $this->validateTeam($user, $book)) {
            return false;
        }

        return $user->can('book:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('book:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Book $book)
    {
        if (! $this->validateTeam($user, $book)) {
            return false;
        }

        return $user->can('book:edit');
    }

    /**
     * Determine whether the user can bulk update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function bulkUpdate(User $user)
    {
        return $user->can('book:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Book $book)
    {
        if (! $this->validateTeam($user, $book)) {
            return false;
        }

        return $user->can('book:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Book $book)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Book $book)
    {
        //
    }
}
