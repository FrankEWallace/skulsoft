<?php

namespace App\Http\Controllers;

use App\Concerns\TeamAccessible;
use App\Http\Requests\Team\ConfigRequest;
use App\Models\Team;
use App\Services\TeamActionService;
use Illuminate\Http\Request;

class TeamActionController extends Controller
{
    use TeamAccessible;

    public function select(Request $request, Team $team, TeamActionService $service)
    {
        $service->select($request, $team);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('team.current_team')]),
        ]);
    }

    public function getPeriods(Request $request, Team $team, TeamActionService $service)
    {
        $periods = $service->getPeriods($request, $team);

        return response()->success([
            'periods' => $periods,
        ]);
    }

    public function storeConfig(ConfigRequest $request, Team $team, TeamActionService $service)
    {
        $this->isAccessible($team);

        $service->storeConfig($request, $team);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('team.team')]),
        ]);
    }

    public function sync(Request $request, Team $team, TeamActionService $service)
    {
        $service->sync($request, $team);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('team.team')]),
        ]);
    }
}
