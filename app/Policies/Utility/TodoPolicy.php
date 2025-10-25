<?php

namespace App\Policies\Utility;

use App\Models\User;
use App\Models\Utility\Todo;
use Illuminate\Auth\Access\HandlesAuthorization;

class TodoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can manage the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manage(User $user, Todo $todo)
    {
        return $user->id == $todo->user_id;
    }
}
