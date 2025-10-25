<?php

namespace App\Actions\Auth;

use App\Actions\SendMailTemplate;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Register
{
    public function execute(Request $request): User
    {
        \DB::beginTransaction();

        $user = User::forceCreate([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'status' => $this->getUserStatus(),
            'meta' => ['activation_token' => Str::uuid()],
        ]);

        // $user->assignRole($this->getUserRole());

        \DB::commit();

        $this->sendVerificationEmail($user);

        return $user;
    }

    /**
     * Request email for registered user
     */
    public function emailRequest(Request $request): void
    {
        if (! config('config.auth.enable_email_verification')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $user = User::whereEmail($request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => trans('global.could_not_find', ['attribute' => trans('user.user')])]);
        }

        if ($user->status != UserStatus::PENDING_VERIFICATION) {
            throw ValidationException::withMessages(['email' => trans('general.errors.invalid_action')]);
        }

        $this->sendVerificationEmail($user);
    }

    /**
     * Get user status
     */
    private function getUserStatus(): string
    {
        if (config('config.auth.enable_email_verification')) {
            return UserStatus::PENDING_VERIFICATION->value;
        } elseif (config('config.auth.enable_account_approval')) {
            return UserStatus::PENDING_APPROVAL->value;
        }

        return UserStatus::ACTIVATED->value;
    }

    /**
     * Get user role
     */
    private function getUserRole(): string
    {
        return 'user';
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail(User $user): void
    {
        if (! config('config.auth.enable_email_verification')) {
            return;
        }

        (new SendMailTemplate)->execute(
            email: $user->email,
            code: 'user-email-verification',
            variables: [
                'name' => $user->name,
                'url' => url('/app/register/verify/'.$user->getMeta('activation_token')),
            ]
        );
    }
}
