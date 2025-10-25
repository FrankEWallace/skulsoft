<?php

namespace App\Http\Controllers\Team;

use App\Concerns\TeamAccessible;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\RoleRequest;
use App\Http\Resources\Team\RoleResource;
use App\Models\Team;
use App\Models\Team\Role;
use App\Services\Team\RoleListService;
use App\Services\Team\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use TeamAccessible;

    public function index(Request $request, Team $team, RoleListService $service)
    {
        $this->isAccessible($team);

        return $service->paginate($request, $team);
    }

    public function store(RoleRequest $request, Team $team, RoleService $service)
    {
        $this->isAccessible($team);

        $team = $service->create($request, $team);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('team.config.role.role')]),
            'team' => RoleResource::make($team),
        ]);
    }

    public function show(Team $team, Role $role): RoleResource
    {
        $this->isAccessible($team);

        return RoleResource::make($role);
    }

    public function destroy(Team $team, Role $role, RoleService $service)
    {
        $this->isAccessible($team);

        $service->deletable($team, $role);

        $role->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('team.config.role.role')]),
        ]);
    }
}
