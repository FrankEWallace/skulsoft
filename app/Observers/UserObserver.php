<?php

namespace App\Observers;

use App\Models\Academic\Period;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @return void
     */
    public function created(User $user)
    {
        $defaultPeriod = Period::query()
            ->byTeam()
            ->where('is_default', true)
            ->first();

        if (! $defaultPeriod) {
            return;
        }

        $preference = $user->preference;
        $preference['academic']['period_id'] = $defaultPeriod->id;
        $user->preference = $preference;
        $user->save();
    }

    /**
     * Handle the User "updated" event.
     *
     * @return void
     */
    public function updated(User $user)
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     *
     * @return void
     */
    public function deleted(User $user)
    {
        \DB::table('model_has_roles')->whereModelType('User')->whereModelId($user->id)->delete();
    }

    /**
     * Handle the User "restored" event.
     *
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}
