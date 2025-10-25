<?php

namespace App\Support;

use Illuminate\Support\Arr;

trait SocialLoginProvider
{
    public function getActiveProviders(): array
    {
        return array_values(collect(Arr::getList('social_login_providers') ?? [])->filter(function ($item) {
            return config('config.auth.enable_'.$item.'_oauth_login') ? true : false;
        })->all());
    }
}
