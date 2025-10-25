<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\UserToken;

class SetPushToken
{
    public function execute(User $user)
    {
        if (! request()->headers->has('push_token')) {
            return;
        }

        $tokenExists = UserToken::query()
            ->where('token', request()->header('push_token'))
            ->where('user_id', $user->id)
            ->exists();

        if ($tokenExists) {
            return;
        }

        UserToken::query()->create([
            'user_id' => $user->id,
            'type' => 'expo-push-token',
            'token' => request()->header('push_token'),
            'platform' => request()->header('device-platform'),
            'version' => request()->header('device-version'),
        ]);
    }
}
