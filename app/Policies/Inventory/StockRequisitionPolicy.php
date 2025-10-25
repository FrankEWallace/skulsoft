<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\StockRequisition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockRequisitionPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, StockRequisition $stockRequisition)
    {
        return $stockRequisition->inventory->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('stock-requisition:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StockRequisition $stockRequisition)
    {
        if (! $this->validateTeam($user, $stockRequisition)) {
            return false;
        }

        return $user->can('stock-requisition:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('stock-requisition:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StockRequisition $stockRequisition)
    {
        if (! $this->validateTeam($user, $stockRequisition)) {
            return false;
        }

        return $user->can('stock-requisition:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StockRequisition $stockRequisition)
    {
        if (! $this->validateTeam($user, $stockRequisition)) {
            return false;
        }

        return $user->can('stock-requisition:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StockRequisition $stockRequisition)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StockRequisition $stockRequisition)
    {
        //
    }
}
