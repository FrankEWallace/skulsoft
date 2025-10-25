<?php

namespace App\Actions\Auth;

use App\Actions\SendMailTemplate;
use App\Enums\UserStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPassword
{
    /**
     * Request for password reset
     */
    public function request(Request $request): void
    {
        $user = $this->getUser($request);

        $code = rand(100000, 999999);

        \DB::table('password_resets')->insert([
            'email' => $request->email,
            'code' => $code,
            'created_at' => now()->toDateTimeString(),
        ]);

        (new SendMailTemplate)->execute(
            email: $user->email,
            code: 'reset-password-confirmation',
            variables: [
                'name' => $user->name,
                'code' => $code,
                'token_lifetime' => config('config.auth.reset_password_token_lifetime', 30),
            ]
        );
    }

    /**
     * Confirm password reset code
     */
    public function confirm(Request $request): void
    {
        $reset = \DB::table('password_resets')->whereEmail($request->email)->whereCode($request->code)->first();

        if (! $reset) {
            throw ValidationException::withMessages(['code' => __('auth.password.errors.code_mismatch')]);
        }

        if (Carbon::now()->addMinutes((int) config('config.auth.reset_password_token_lifetime', 30)) < Carbon::now()) {
            throw ValidationException::withMessages(['code' => __('auth.password.errors.code_expired')]);
        }
    }

    /**
     * Reset password
     */
    public function reset(Request $request): void
    {
        $user = $this->getUser($request);

        $this->confirm($request);

        if (Hash::check($request->new_password, $user->password)) {
            throw ValidationException::withMessages(['message' => __('auth.password.errors.different')]);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        \DB::table('password_resets')->whereEmail($request->email)->delete();

        (new SendMailTemplate)->execute(
            email: $user->email,
            code: 'reset-password-alert',
            variables: [
                'name' => $user->name,
                'url' => url('/'),
            ]
        );
    }

    /**
     * Validate user for reset password
     */
    private function getUser(Request $request): User
    {
        $user = User::whereEmail($request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => __('auth.password.user')]);
        }

        if ($user->status != UserStatus::ACTIVATED) {
            throw ValidationException::withMessages(['email' => __('auth.password.errors.account_not_activated')]);
        }

        return $user;
    }
}
