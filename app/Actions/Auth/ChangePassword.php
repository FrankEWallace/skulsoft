<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChangePassword
{
    public function execute(Request $request)
    {
        if (! \Auth::user()->password) {
            throw ValidationException::withMessages(['current_password' => __('general.errors.invalid_action')]);
        }

        if (! \Hash::check($request->current_password, \Auth::user()->password)) {
            throw ValidationException::withMessages(['current_password' => __('auth.password.errors.password_mismatch')]);
        }

        $user = \Auth::user();
        $user->password = bcrypt($request->new_password);
        $user->save();
    }
}
