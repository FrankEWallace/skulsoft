<?php

namespace App\Actions\Auth;

use App\Actions\SendMailTemplate;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailVerification
{
    public function execute(Request $request): void
    {
        $user = $this->getUser($request);

        $user->email_verified_at = now()->toDateTimeString();
        $user->status = config('config.auth.enable_account_approval') ? UserStatus::PENDING_APPROVAL : UserStatus::ACTIVATED;
        $user->save();

        (new SendMailTemplate)->execute(
            email: $user->email,
            code: 'account-registered',
            variables: [
                'name' => $user->name,
                'url' => url('/'),
            ]
        );
    }

    /**
     * Get user from verification token
     */
    private function getUser(Request $request): User
    {
        $user = User::where('meta->activation_token', $request->token)->first();

        if (! $user) {
            throw ValidationException::withMessages(['message' => __('auth.register.errors.invalid_verification_token')]);
        }

        if ($user->status != UserStatus::PENDING_VERIFICATION) {
            throw ValidationException::withMessages(['message' => __('general.errors.invalid_action')]);
        }

        return $user;
    }
}
