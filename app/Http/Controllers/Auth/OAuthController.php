<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\OAuthLogin;
use App\Http\Controllers\Controller;
use App\Support\SocialLoginProvider;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    use SocialLoginProvider;

    private function isValidProvider($provider)
    {
        if (! in_array($provider, $this->getActiveProviders())) {
            abort(404);
        }
    }

    public function redirect($provider)
    {
        $this->isValidProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider, OAuthLogin $login)
    {
        $this->isValidProvider($provider);

        $login->execute($provider);

        return redirect('/');
    }
}
