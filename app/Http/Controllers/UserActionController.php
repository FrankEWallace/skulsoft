<?php

namespace App\Http\Controllers;

use App\Actions\UserScopeUpdate;
use App\Actions\UserStatusUpdate;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserActionController extends Controller
{
    public function search(Request $request) {}

    public function status(Request $request, User $user, UserStatusUpdate $action)
    {
        $this->authorize('update', $user);

        $action->execute($request, $user);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('user.user')]),
        ]);
    }

    public function updateScope(Request $request, UserScopeUpdate $action)
    {
        $user = $action->execute($request);

        return response()->success([
            'user' => AuthUserResource::make($user),
        ]);
    }
}
