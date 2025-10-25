<?php

namespace App\Actions;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class UserStatusUpdate
{
    public function execute(Request $request, User $user)
    {
        $request->validate([
            'status' => [new Enum(UserStatus::class)],
        ]);

        $status = UserStatus::tryFrom($request->status);

        if ($user->status === UserStatus::PENDING_APPROVAL
            && ! in_array($status, [
                UserStatus::ACTIVATED, UserStatus::DISAPPROVED,
            ])
        ) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($user->status === UserStatus::ACTIVATED
            && ! in_array($status, [
                UserStatus::BANNED,
            ])
        ) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($user->status === UserStatus::BANNED
            && ! in_array($status, [
                UserStatus::ACTIVATED,
            ])
        ) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $user->status = $status;
        $user->save();
    }
}
