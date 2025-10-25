<?php

namespace App\Policies\Student;

use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, Student $student)
    {
        return $student->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['student:read', 'student:create', 'student:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('student:read');
    }

    /**
     * Determine whether the user can view summary of any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function selfSummary(User $user)
    {
        return $user->hasRole('student');
    }

    /**
     * Determine whether the user can view summary of any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewSummary(User $user)
    {
        return $user->can('student:admin-access') || $user->can('student:summary');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read');
    }

    /**
     * Determine whether the user can allocate service for the student.
     */
    public function setBatchServiceAllocation(User $user)
    {
        return $user->can('student:service-allocation');
    }

    /**
     * Determine whether the user can set fee for the student.
     */
    public function setFee(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:set');
    }

    /**
     * Determine whether the user can set fee for the student.
     */
    public function setBatchFeeAllocation(User $user)
    {
        return $user->can('student:read') && $user->can('fee:set');
    }

    /**
     * Determine whether the user can update fee for the student.
     */
    public function updateFee(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:edit');
    }

    /**
     * Determine whether the user can set fee for the student.
     */
    public function resetFee(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:reset');
    }

    /**
     * Determine whether the user can set custom concession for the student.
     */
    public function setCustomConcession(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('fee:set') && $user->can('fee:custom-concession');
    }

    /**
     * Determine whether the user can pay fee via bank transfer for the student.
     */
    public function bankTransfer(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:bank-transfer');
    }

    /**
     * Determine whether the user can take action on pay fee via bank transfer for the student.
     */
    public function bankTransferAction(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:bank-transfer-action');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function makePayment(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:payment');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function makeHeadWisePayment(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:head-wise-payment');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function exportPayment(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function updatePayment(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:edit-payment');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function cancelPayment(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:read') && $user->can('fee:cancel-payment');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function promotion(User $user)
    {
        return $user->can('student:promotion');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function transfer(User $user)
    {
        return $user->can('student:transfer');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function editRequest(User $user, Student $student)
    {
        return config('config.student.allow_student_to_submit_contact_edit_request', false);
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function transferRequest(User $user)
    {
        return $user->can('student:transfer-request');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function listAttendance(User $user)
    {
        return $user->can('student:list-attendance');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function markAttendance(User $user)
    {
        return $user->can('student:mark-attendance') || $user->can('student:incharge-wise-mark-attendance');
    }

    /**
     * Determine whether the user can pay fee for the student.
     */
    public function manageRecord(User $user)
    {
        if ($user->can('student:edit')) {
            return true;
        }

        return $user->can('student-record:manage');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('student:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:edit');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function bulkUpdate(User $user)
    {
        return $user->can('student:edit');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateRecord(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Student $student)
    {
        if (! $this->validateTeam($user, $student)) {
            return false;
        }

        return $user->can('student:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Student $student)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Student $student)
    {
        //
    }
}
