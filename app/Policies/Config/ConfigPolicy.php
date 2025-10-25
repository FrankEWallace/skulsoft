<?php

namespace App\Policies\Config;

use App\Lists\ConfigType;
use App\Models\Config\Config;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConfigPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Config $config)
    {
        //
    }

    /**
     * Determine whether the user can store models.
     *
     * @return mixed
     */
    public function store(User $user)
    {
        $type = request('type');

        if (in_array($type, ConfigType::TYPES)) {
            return $user->can('config:store');
        }

        if (in_array($type, ConfigType::MODULE_TYPES)) {
            return $user->can($type.':config');
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Config $config)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Config $config)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, Config $config)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Config $config)
    {
        //
    }
}
