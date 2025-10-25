<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class OAuthLogin
{
    public function execute(string $provider): void
    {
        $providerUser = Socialite::driver($provider)->user();

        $user = User::whereEmail($providerUser->email)->first();

        if ($user && $user->status != UserStatus::ACTIVATED) {
            abort(404);
        }

        if (! $user) {
            abort(398, 'Could not find user with email: '.$providerUser->email);
            // $user = User::forceCreate([
            //     'email' => $providerUser->email,
            // ]);

            // $user->name = $providerUser->name;
            // $user->status = 'activated';
            // $user->meta = ['oauth_provider' => $provider];
            // $user->save();

            // $user->assignRole('user');
        }

        \Auth::login($user);

        $user->setCurrentTeamId();

        $user->validateStatus();
    }
}
