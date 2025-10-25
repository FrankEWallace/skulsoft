<?php

namespace App\Policies\Reception;

use App\Models\Reception\Enquiry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnquiryPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Enquiry $enquiry)
    {
        return $enquiry->period->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('enquiry:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Enquiry $enquiry)
    {
        if (! $this->validateTeam($user, $enquiry)) {
            return false;
        }

        return $user->can('enquiry:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('enquiry:create');
    }

    /**
     * Determine whether the user can take action on the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function action(User $user, Enquiry $enquiry)
    {
        if (! $this->validateTeam($user, $enquiry)) {
            return false;
        }

        return $user->can('enquiry:action');
    }

    /**
     * Determine whether the user can take bulk action on the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function bulkAction(User $user)
    {
        return $user->can('enquiry:action');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Enquiry $enquiry)
    {
        if (! $this->validateTeam($user, $enquiry)) {
            return false;
        }

        return $user->can('enquiry:edit');
    }

    /**
     * Determine whether the user can bulk update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function bulkUpdate(User $user)
    {
        return $user->can('enquiry:edit');
    }

    /**
     * Determine whether the user can follow up the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function followUp(User $user, Enquiry $enquiry)
    {
        if (! $this->validateTeam($user, $enquiry)) {
            return false;
        }

        return $user->can('enquiry:follow-up');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Enquiry $enquiry)
    {
        if (! $this->validateTeam($user, $enquiry)) {
            return false;
        }

        return $user->can('enquiry:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Enquiry $enquiry)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Enquiry $enquiry)
    {
        //
    }
}
