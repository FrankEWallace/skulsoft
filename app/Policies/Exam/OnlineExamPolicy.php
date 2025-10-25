<?php

namespace App\Policies\Exam;

use App\Models\Exam\OnlineExam;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OnlineExamPolicy
{
    use HandlesAuthorization;

    private function validatePeriod(User $user, OnlineExam $onlineExam)
    {
        return $onlineExam->period_id == $user->current_period_id;
    }

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('online-exam:create') || $user->can('online-exam:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('online-exam:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, OnlineExam $onlineExam)
    {
        if (! $this->validatePeriod($user, $onlineExam)) {
            return false;
        }

        return $user->can('online-exam:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('online-exam:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, OnlineExam $onlineExam)
    {
        if (! $this->validatePeriod($user, $onlineExam)) {
            return false;
        }

        return $user->can('online-exam:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, OnlineExam $onlineExam)
    {
        if (! $this->validatePeriod($user, $onlineExam)) {
            return false;
        }

        return $user->can('online-exam:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, OnlineExam $onlineExam)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, OnlineExam $onlineExam)
    {
        //
    }
}
