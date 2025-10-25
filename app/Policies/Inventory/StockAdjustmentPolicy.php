<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\StockAdjustment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockAdjustmentPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, StockAdjustment $stockAdjustment)
    {
        return $stockAdjustment->inventory->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('stock-adjustment:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StockAdjustment $stockAdjustment)
    {
        if (! $this->validateTeam($user, $stockAdjustment)) {
            return false;
        }

        return $user->can('stock-adjustment:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('stock-adjustment:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StockAdjustment $stockAdjustment)
    {
        if (! $this->validateTeam($user, $stockAdjustment)) {
            return false;
        }

        return $user->can('stock-adjustment:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StockAdjustment $stockAdjustment)
    {
        if (! $this->validateTeam($user, $stockAdjustment)) {
            return false;
        }

        return $user->can('stock-adjustment:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StockAdjustment $stockAdjustment)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StockAdjustment $stockAdjustment)
    {
        //
    }
}
