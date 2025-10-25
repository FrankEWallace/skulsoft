<?php

namespace App\Policies\Employee\Leave;

use App\Concerns\SubordinateAccess;
use App\Models\Employee\Leave\Request as LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RequestPolicy
{
    use HandlesAuthorization, SubordinateAccess;

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('leave-request:create') || $user->can('leave-request:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('leave-request:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, LeaveRequest $leaveRequest)
    {
        if (! $user->can('leave-request:read')) {
            return false;
        }

        if ($leaveRequest?->model?->team_id != $user->current_team_id) {
            return false;
        }

        if ($user->id == $leaveRequest->model->user_id) {
            return true;
        }

        return $this->isAccessibleEmployee($leaveRequest->model);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('leave-request:create');
    }

    /**
     * Determine whether the user can take action on the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function action(User $user, LeaveRequest $leaveRequest)
    {
        if (! $user->can('leave-request:action')) {
            return false;
        }

        if ($leaveRequest?->model?->team_id != $user->current_team_id) {
            return false;
        }

        return $this->isAccessibleEmployee($leaveRequest->model);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, LeaveRequest $leaveRequest)
    {
        if (! $user->can('leave-request:edit')) {
            return false;
        }

        if ($leaveRequest?->model?->team_id != $user->current_team_id) {
            return false;
        }

        if ($user->id == $leaveRequest->model->user_id) {
            return true;
        }

        return $this->isAccessibleEmployee($leaveRequest->model);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, LeaveRequest $leaveRequest)
    {
        if (! $user->can('leave-request:edit')) {
            return false;
        }

        if ($leaveRequest?->model?->team_id != $user->current_team_id) {
            return false;
        }

        if ($user->id == $leaveRequest->model->user_id) {
            return true;
        }

        return $this->isAccessibleEmployee($leaveRequest->model);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, LeaveRequest $leaveRequest)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest)
    {
        //
    }
}
