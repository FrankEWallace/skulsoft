<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\StockReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockReturnPolicy
{
    use HandlesAuthorization;

    private function validateTeam(User $user, StockReturn $stockReturn)
    {
        return $stockReturn->inventory->team_id == $user->current_team_id;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('stock-return:read');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StockReturn $stockReturn)
    {
        if (! $this->validateTeam($user, $stockReturn)) {
            return false;
        }

        return $user->can('stock-return:read');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('stock-return:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StockReturn $stockReturn)
    {
        if (! $this->validateTeam($user, $stockReturn)) {
            return false;
        }

        return $user->can('stock-return:edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StockReturn $stockReturn)
    {
        if (! $this->validateTeam($user, $stockReturn)) {
            return false;
        }

        return $user->can('stock-return:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StockReturn $stockReturn)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StockReturn $stockReturn)
    {
        //
    }
}
