<?php

namespace App\Listeners;

use App\Events\Auth\UserLogin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mint\Service\Actions\CheckForUpdate;

class UserLoginListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(UserLogin $event)
    {
        (new CheckForUpdate)->execute();
    }
}
