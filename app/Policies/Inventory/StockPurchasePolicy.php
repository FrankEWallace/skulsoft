<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\StockPurchase;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockPurchasePolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, StockPurchase $stockPurchase)
    {
        return $stockPurchase->inventory->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('stock-purchase:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StockPurchase $stockPurchase)
    {
        if (! $this->validateTeam($user, $stockPurchase)) {
            return false;
        }

        return $user->can('stock-purchase:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('stock-purchase:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StockPurchase $stockPurchase)
    {
        if (! $this->validateTeam($user, $stockPurchase)) {
            return false;
        }

        return $user->can('stock-purchase:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StockPurchase $stockPurchase)
    {
        if (! $this->validateTeam($user, $stockPurchase)) {
            return false;
        }

        return $user->can('stock-purchase:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StockPurchase $stockPurchase)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StockPurchase $stockPurchase)
    {
        //
    }
}
