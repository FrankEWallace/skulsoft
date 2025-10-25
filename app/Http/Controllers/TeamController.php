<?php

namespace App\Http\Controllers;

use App\Concerns\TeamAccessible;
use App\Http\Requests\TeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Services\TeamListService;
use App\Services\TeamService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use TeamAccessible;

    public function preRequisite(Request $request, TeamService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, TeamListService $service)
    {
        return $service->paginate($request);
    }

    public function store(TeamRequest $request, TeamService $service)
    {
        $this->canAdd();

        $team = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('team.team')]),
            'team' => TeamResource::make($team),
        ]);
    }

    public function show(Team $team, TeamService $service): TeamResource
    {
        $this->isAccessible($team);

        $team->load('organization');

        return TeamResource::make($team);
    }

    public function update(TeamRequest $request, Team $team, TeamService $service)
    {
        $this->isAccessible($team);

        $service->update($request, $team);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('team.team')]),
        ]);
    }

    public function destroy(Team $team, TeamService $service)
    {
        $this->isAccessible($team);

        $service->deletable($team);

        $team->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('team.team')]),
        ]);
    }
}
