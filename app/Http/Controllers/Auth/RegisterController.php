<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\EmailVerification;
use App\Actions\Auth\Register;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterEmailRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /**
     * Instantiate a new controller instance
     */
    public function __construct()
    {
        $this->middleware('feature.available:auth.enable_registration')->only('register');
        $this->middleware('feature.available:auth.enable_email_verification')->only('verify');
    }

    /**
     * Register user
     */
    public function register(RegisterRequest $request, Register $register)
    {
        $user = $register->execute($request);

        return response()->success(['message' => trans('auth.register.registered_status_'.$user->status->value)]);
    }

    /**
     * Request email for registered user
     */
    public function emailRequest(RegisterEmailRequest $request, Register $register)
    {
        $register->emailRequest($request);

        return response()->success(['message' => trans('auth.register.pending_verification_email_sent')]);
    }

    /**
     * Verify registered user's email
     */
    public function verify(Request $request, EmailVerification $emailVerification)
    {
        $emailVerification->execute($request);

        return response()->success(['message' => trans('auth.register.email_verified')]);
    }
}
