<?php

namespace App\Actions\Config;

use Closure;

class SetPusherConfig
{
    public function handle($config, Closure $next)
    {
        if (! config('config.notification.enable_pusher_notification')) {
            return $next($config);
        }

        config([
            'broadcasting.connections.pusher.key' => config('config.notification.pusher_app_key'),
            'broadcasting.connections.pusher.secret' => config('config.notification.pusher_app_secret'),
            'broadcasting.connections.pusher.app_id' => config('config.notification.pusher_app_id'),
            'broadcasting.connections.pusher.options.cluster' => config('config.notification.pusher_app_cluster'),
        ]);

        return $next($config);
    }
}
