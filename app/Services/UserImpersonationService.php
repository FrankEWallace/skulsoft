<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserImpersonationService
{
    public function impersonate(string $uuid)
    {
        $authUser = auth()->user();

        $user = User::query()
            ->whereUuid($uuid)
            ->where('id', '!=', $authUser->id)
            ->getOrFail(trans('user.user'));

        $user->validateStatus();

        session()->put('impersonate', $authUser->uuid);

        \Auth::guard('web')->login($user);

        $user->setCurrentTeamId();

        activity()
            ->causedBy($authUser)
            ->withProperties(['impersonate_as' => $user->name])
            ->log(trans('user.impersonate'));

        return $user;
    }

    public function unimpersonate()
    {
        $authUser = auth()->user();

        $userUuid = session()->pull('impersonate');

        if (! $userUuid) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $user = User::query()
            ->whereUuid($userUuid)
            ->getOrFail(trans('user.user'));

        $user->validateStatus();

        session()->forget('impersonate');

        \Auth::guard('web')->login($user);

        activity()
            ->causedBy($user)
            ->withProperties(['unimpersonate_from' => $authUser->name])
            ->log(trans('user.unimpersonate'));

        return $user;
    }
}
