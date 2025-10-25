<?php

namespace App\Policies\Approval;

use App\Models\Approval\Request;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RequestPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Request $approvalRequest)
    {
        return true; // other team members can see other team requests if they are in the approval levels
        // return $approvalRequest->type->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('approval-request:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Request $approvalRequest)
    {
        if (! $this->validateTeam($user, $approvalRequest)) {
            return false;
        }

        return $user->can('approval-request:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('approval-request:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Request $approvalRequest)
    {
        if (! $this->validateTeam($user, $approvalRequest)) {
            return false;
        }

        return $user->can('approval-request:edit');
    }

    /**
     * Determine whether the user can take action on the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function action(User $user, Request $approvalRequest)
    {
        if (! $this->validateTeam($user, $approvalRequest)) {
            return false;
        }

        if (! $user->can('approval-request:action')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Request $approvalRequest)
    {
        if (! $this->validateTeam($user, $approvalRequest)) {
            return false;
        }

        return $user->can('approval-request:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Request $approvalRequest)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Request $approvalRequest)
    {
        //
    }
}
