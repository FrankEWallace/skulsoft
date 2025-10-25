<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function denyAuthUser(?User $user)
    {
        if (auth()->user()->is_default) {
            return;
        }

        if (is_null($user)) {
            return;
        }

        if ($user->id == auth()->id()) {
            throw ValidationException::withMessages(['message' => trans('user.errors.auth_user_permission_denied')]);
        }
    }

    public function denySuperAdmin(?User $user)
    {
        if (is_null($user)) {
            return;
        }

        if ($user->is_default) {
            throw ValidationException::withMessages(['message' => trans('user.errors.default_user_permission_denied')]);
        }
    }

    public function denyAdmin(?User $user)
    {
        if (is_null($user)) {
            return;
        }

        if ($user->is_default) {
            throw ValidationException::withMessages(['message' => trans('user.errors.default_user_permission_denied')]);
        }

        if (! auth()->user()->is_default && $user->hasRole('admin')) {
            throw ValidationException::withMessages(['message' => trans('user.errors.admin_user_permission_denied')]);
        }
    }
}
