<?php

namespace App\Actions\Auth;

use App\Http\Resources\AuthUserResource;
use Illuminate\Http\Request;

class ProfileUpdate
{
    public function execute(Request $request)
    {
        $user = \Auth::user();

        $user->name = $request->has('name') ? $request->name : $request->name;

        $user->save();

        return AuthUserResource::make($user);
    }
}
