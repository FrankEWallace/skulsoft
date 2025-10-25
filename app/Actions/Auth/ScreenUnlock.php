<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ScreenUnlock
{
    public function execute(Request $request)
    {
        if (! Hash::check($request->password, \Auth::user()->password)) {
            throw ValidationException::withMessages(['password' => __('auth.login.failed')]);
        }

        session()->forget('screen_lock');
    }
}
