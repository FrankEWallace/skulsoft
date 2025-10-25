<?php

namespace App\Policies\Academic;

use App\Models\Academic\CertificateTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificateTemplatePolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, CertificateTemplate $certificateTemplate)
    {
        return $certificateTemplate->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can request for pre-requisites.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->canAny(['certificate-template:create', 'certificate-template:edit']);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('certificate-template:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, CertificateTemplate $certificateTemplate)
    {
        if (! $this->validateTeam($user, $certificateTemplate)) {
            return false;
        }

        return $user->can('certificate-template:read') || $user->can('certificate:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('certificate-template:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, CertificateTemplate $certificateTemplate)
    {
        if (! $this->validateTeam($user, $certificateTemplate)) {
            return false;
        }

        return $user->can('certificate-template:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, CertificateTemplate $certificateTemplate)
    {
        if (! $this->validateTeam($user, $certificateTemplate)) {
            return false;
        }

        return $user->can('certificate-template:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, CertificateTemplate $certificateTemplate)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, CertificateTemplate $certificateTemplate)
    {
        //
    }
}
