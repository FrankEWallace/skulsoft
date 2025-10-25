<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, string $permission)
    {
        return $user->can($permission.':read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Document  $member
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, string $permission)
    {
        return $user->can($permission.':read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, string $permission)
    {
        return $user->can($permission.':create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Document $member, string $permission)
    {
        return $user->can($permission.':edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Document $member, string $permission)
    {
        return $user->can($permission.':delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Document $member, string $permission)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Document $member, string $permission)
    {
        //
    }
}
