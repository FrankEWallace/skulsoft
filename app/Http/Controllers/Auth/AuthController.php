<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SetPushToken;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuthUserResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Get logged in user
     */
    public function me(Request $request)
    {
        $user = \Auth::user();

        if ($user) {
            $user->validateStatus();

            $user->validateIp($request->ip());
        }

        (new SetPushToken)->execute($user);

        return AuthUserResource::make($user);
    }
}
