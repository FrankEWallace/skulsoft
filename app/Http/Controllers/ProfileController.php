<?php

namespace App\Http\Controllers;

use App\Actions\Auth\AccountUpdate;
use App\Actions\Auth\ChangePassword;
use App\Actions\Auth\ProfileUpdate;
use App\Actions\Auth\ProfileUpdateVerification;
use App\Actions\Auth\RemoveAvatar;
use App\Actions\Auth\UploadAvatar;
use App\Actions\Auth\UserPreference;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\UserAccountRequest;
use App\Http\Requests\UserPreferenceRequest;
use App\Http\Requests\UserProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    public function password(ChangePasswordRequest $request, ChangePassword $changePassword)
    {
        Gate::authorize('password:update');

        $changePassword->execute($request);

        return response()->success(['message' => trans('auth.password.changed')]);
    }

    public function update(UserProfileRequest $request, ProfileUpdate $action)
    {
        Gate::authorize('profile:update');

        $user = $action->execute($request);

        return response()->success([
            'user' => $user,
            'message' => trans('global.updated', ['attribute' => trans('user.profile.profile')]),
        ]);
    }

    public function account(UserAccountRequest $request, AccountUpdate $action)
    {
        Gate::authorize('profile:update');

        return response()->success($action->execute($request));
    }

    public function verify(Request $request, ProfileUpdateVerification $action)
    {
        Gate::authorize('profile:update');

        $user = $action->execute($request);

        return response()->success([
            'user' => $user,
            'profile_updated' => true,
            'message' => trans('global.updated', ['attribute' => trans('user.profile.profile')]),
        ]);
    }

    public function preference(UserPreferenceRequest $request, UserPreference $userPreference)
    {
        // Gate::authorize('profile:update');

        $user = $userPreference->execute($request);

        return response()->success([
            'user' => $user,
            'message' => trans('global.updated', ['attribute' => trans('user.preference.preference')]),
        ]);
    }

    public function uploadAvatar(Request $request, UploadAvatar $action)
    {
        Gate::authorize('profile:update');

        $action->execute($request);

        return response()->success(['message' => trans('global.uploaded', ['attribute' => trans('user.avatar')])]);
    }

    public function removeAvatar(Request $request, RemoveAvatar $action)
    {
        Gate::authorize('profile:update');

        $action->execute($request);

        return response()->success(['message' => trans('global.removed', ['attribute' => trans('user.avatar')])]);
    }
}
