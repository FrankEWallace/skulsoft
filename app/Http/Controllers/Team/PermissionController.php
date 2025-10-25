<?php

namespace App\Http\Controllers\Team;

use App\Actions\UserSearch;
use App\Concerns\TeamAccessible;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\UserWisePermissionRequest;
use App\Http\Resources\UserResource;
use App\Models\Team;
use App\Services\Team\PermissionSearchService;
use App\Services\Team\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use TeamAccessible;

    public function preRequisite(Request $request, Team $team, PermissionService $service)
    {
        $this->isAccessible($team);

        return response()->ok($service->preRequisite($request, $team));
    }

    public function roleWiseAssign(Request $request, Team $team, PermissionService $service)
    {
        $this->isAccessible($team);

        $service->roleWiseAssign($request, $team);

        return response()->success([
            'message' => __('global.assigned', ['attribute' => __('team.config.permission.permission')]),
        ]);
    }

    public function search(Request $request, Team $team, PermissionSearchService $service)
    {
        $this->isAccessible($team);

        return response()->ok($service->search($request));
    }

    public function searchUser(Request $request, Team $team, UserSearch $action)
    {
        $this->isAccessible($team);

        return UserResource::collection($action->execute($request, $team));
    }

    public function userWiseAssign(UserWisePermissionRequest $request, Team $team, PermissionService $service)
    {
        $this->isAccessible($team);

        $service->userWiseAssign($request, $team);

        return response()->success([
            'message' => __('global.assigned', ['attribute' => __('team.config.permission.permission')]),
        ]);
    }
}
