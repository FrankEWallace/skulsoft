<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScreenLock
{
    public function execute(Request $request)
    {
        if (! config('config.auth.enable_screen_lock')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.feature_not_available')]);
        }

        session()->put('screen_lock', true);
    }
}
