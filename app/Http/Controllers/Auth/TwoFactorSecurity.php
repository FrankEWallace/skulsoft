<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorSecurityRequest;

class TwoFactorSecurity extends Controller
{
    /**
     * Instantiate a new controller instance
     */
    public function __construct()
    {
        $this->middleware('feature.available:auth.enable_two_factor_security');
    }

    /**
     * Unlock screen
     */
    public function __invoke(TwoFactorSecurityRequest $request)
    {
        \Auth::user()->validateTwoFactor($request->code);

        return response()->ok([]);
    }
}
