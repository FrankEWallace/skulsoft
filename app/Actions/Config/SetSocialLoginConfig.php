<?php

namespace App\Actions\Config;

use App\Support\SocialLoginProvider;
use Closure;

class SetSocialLoginConfig
{
    use SocialLoginProvider;

    public function handle($config, Closure $next)
    {
        foreach ($this->getActiveProviders() as $provider) {
            config([
                'services.'.$provider.'.client_id' => config('config.auth.'.$provider.'_client_id'),
                'services.'.$provider.'.client_secret' => config('config.auth.'.$provider.'_client_secret'),
                'services.'.$provider.'.redirect' => url('/auth/'.$provider.'/callback'),
            ]);
        }

        return $next($config);
    }
}
