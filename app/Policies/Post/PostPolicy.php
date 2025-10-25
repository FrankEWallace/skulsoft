<?php

namespace App\Policies\Post;

use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Post $post)
    {
        return $post->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('post:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Post $post)
    {
        if (! $this->validateTeam($user, $post)) {
            return false;
        }

        return $user->can('post:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('post:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Post $post)
    {
        if (! $this->validateTeam($user, $post)) {
            return false;
        }

        return $user->can('post:edit');
    }

    /**
     * Determine whether the user can comment on the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function comment(User $user, Post $post)
    {
        if (! $this->validateTeam($user, $post)) {
            return false;
        }

        if (! $user->can('post:comment')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Post $post)
    {
        if (! $this->validateTeam($user, $post)) {
            return false;
        }

        return $user->can('post:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Post $post)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Post $post)
    {
        //
    }
}
