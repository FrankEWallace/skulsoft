<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\StockCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockCategoryPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, StockCategory $stockCategory)
    {
        return $stockCategory->inventory->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('stock-category:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StockCategory $stockCategory)
    {
        if (! $this->validateTeam($user, $stockCategory)) {
            return false;
        }

        return $user->can('stock-category:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('stock-category:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StockCategory $stockCategory)
    {
        if (! $this->validateTeam($user, $stockCategory)) {
            return false;
        }

        return $user->can('stock-category:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StockCategory $stockCategory)
    {
        if (! $this->validateTeam($user, $stockCategory)) {
            return false;
        }

        return $user->can('stock-category:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StockCategory $stockCategory)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StockCategory $stockCategory)
    {
        //
    }
}
