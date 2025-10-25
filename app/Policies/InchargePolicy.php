<?php

namespace App\Policies;

use App\Models\Incharge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InchargePolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Incharge $batchIncharge)
    {
        return true;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user, $permission = 'batch')
    {
        return $user->canAny([$permission.'-incharge:create', $permission.'-incharge:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, $permission = 'batch')
    {
        return $user->can($permission.'-incharge:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Incharge $batchIncharge, $permission = 'batch')
    {
        if (! $this->validateTeam($user, $batchIncharge)) {
            return false;
        }

        return $user->can($permission.'-incharge:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, $permission = 'batch')
    {
        return $user->can($permission.'-incharge:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Incharge $batchIncharge, $permission = 'batch')
    {
        if (! $this->validateTeam($user, $batchIncharge)) {
            return false;
        }

        return $user->can($permission.'-incharge:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Incharge $batchIncharge, $permission = 'batch')
    {
        if (! $this->validateTeam($user, $batchIncharge)) {
            return false;
        }

        return $user->can($permission.'-incharge:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Incharge $batchIncharge, $permission = 'batch')
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Incharge $batchIncharge, $permission = 'batch')
    {
        //
    }
}
