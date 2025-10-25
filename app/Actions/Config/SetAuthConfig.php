<?php

namespace App\Actions\Config;

use Closure;

class SetAuthConfig
{
    public function handle($config, Closure $next)
    {
        config([
            'services.google.client_id' => config('config.auth.google_client_id'),
            'services.google.client_secret' => config('config.auth.google_client_secret'),
            'services.google.redirect' => config('config.auth.google_callback_url'),
            'services.facebook.client_id' => config('config.auth.facebook_client_id'),
            'services.facebook.client_secret' => config('config.auth.facebook_client_secret'),
            'services.facebook.redirect' => config('config.auth.facebook_callback_url'),
            'services.twitter.client_id' => config('config.auth.twitter_client_id'),
            'services.twitter.client_secret' => config('config.auth.twitter_client_secret'),
            'services.twitter.redirect' => config('config.auth.twitter_callback_url'),
            'services.github.client_id' => config('config.auth.github_client_id'),
            'services.github.client_secret' => config('config.auth.github_client_secret'),
            'services.github.redirect' => config('config.auth.github_callback_url'),
        ]);

        return $next($config);
    }
}
