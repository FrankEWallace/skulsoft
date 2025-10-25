<?php

namespace App\Policies\Hostel;

use App\Models\Hostel\RoomAllocation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomAllocationPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, RoomAllocation $roomAllocation)
    {
        return $roomAllocation->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can fetch prerequisites any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function preRequisite(User $user)
    {
        return $user->can('hostel-room-allocation:create') || $user->can('hostel-room-allocation:edit');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('hostel-room-allocation:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, RoomAllocation $roomAllocation)
    {
        if (! $this->validateTeam($user, $roomAllocation)) {
            return false;
        }

        return $user->can('hostel-room-allocation:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('hostel-room-allocation:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, RoomAllocation $roomAllocation)
    {
        if (! $this->validateTeam($user, $roomAllocation)) {
            return false;
        }

        return $user->can('hostel-room-allocation:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, RoomAllocation $roomAllocation)
    {
        if (! $this->validateTeam($user, $roomAllocation)) {
            return false;
        }

        return $user->can('hostel-room-allocation:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, RoomAllocation $roomAllocation)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, RoomAllocation $roomAllocation)
    {
        //
    }
}
