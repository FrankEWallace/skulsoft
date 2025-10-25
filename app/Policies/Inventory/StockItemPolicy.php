<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\StockItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockItemPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, StockItem $stockItem)
    {
        return $stockItem->category->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('stock-item:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StockItem $stockItem)
    {
        if (! $this->validateTeam($user, $stockItem)) {
            return false;
        }

        return $user->can('stock-item:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('stock-item:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StockItem $stockItem)
    {
        if (! $this->validateTeam($user, $stockItem)) {
            return false;
        }

        return $user->can('stock-item:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StockItem $stockItem)
    {
        if (! $this->validateTeam($user, $stockItem)) {
            return false;
        }

        return $user->can('stock-item:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StockItem $stockItem)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StockItem $stockItem)
    {
        //
    }
}
