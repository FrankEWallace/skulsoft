<?php

namespace App\Listeners;

use App\Events\Auth\UserLogin;
use App\Helpers\SysHelper;
use Mint\Service\Actions\CheckForUpdate;

class UserLoginListener
{
    /**
     * Handle the event.
     */
    public function handle(UserLogin $event)
    {
        // Skip if already checked today
        if (SysHelper::getApp('UPDATE') == today()->toDateString()) {
            return;
        }

        // Mark today as checked immediately so subsequent logins don't block
        SysHelper::setApp(['UPDATE' => today()->toDateString()]);

        // Run the update check with a short timeout to avoid blocking the request
        $orig = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', 3);

        try {
            (new CheckForUpdate)->execute();
        } catch (\Throwable $e) {
            // Silently ignore — update check must never block a user login
        } finally {
            ini_set('default_socket_timeout', $orig);
        }
    }
}
