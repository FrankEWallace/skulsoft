<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordRequest;

class PasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('feature.available:auth.enable_reset_password');
    }

    public function password(PasswordRequest $request, ResetPassword $resetPassword)
    {
        $resetPassword->request($request);

        return response()->success(['message' => __('auth.password.sent')]);
    }

    public function confirm(PasswordRequest $request, ResetPassword $resetPassword)
    {
        $resetPassword->confirm($request);

        return response()->ok([]);
    }

    public function reset(PasswordRequest $request, ResetPassword $resetPassword)
    {
        $resetPassword->reset($request);

        return response()->success(['message' => __('auth.password.reset')]);
    }
}
