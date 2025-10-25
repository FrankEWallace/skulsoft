<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserListService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function preRequisite(UserService $service)
    {
        $this->authorize('preRequisite', User::class);

        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, UserListService $service)
    {
        $this->authorize('viewAny', User::class);

        return $service->paginate($request);
    }

    public function store(UserRequest $request, UserService $service)
    {
        $this->authorize('create', User::class);

        $user = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('user.user')]),
            'user' => UserResource::make($user),
        ]);
    }

    public function show(Request $request, User $user, UserService $service)
    {
        $service->isAccessible($user);

        $this->authorize('view', $user);

        $request->merge(['with_direct_permission' => true]);

        $user->load('roles', 'permissions');

        return UserResource::make($user);
    }

    public function update(UserRequest $request, User $user, UserService $service)
    {
        $service->isAccessible($user);

        $this->authorize('update', $user);

        $service->update($request, $user);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('user.user')]),
        ]);
    }

    public function destroy(User $user, UserService $service)
    {
        $service->isAccessible($user);

        $this->authorize('delete', $user);

        $service->deletable($user);

        $user->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('user.user')]),
        ]);
    }
}
