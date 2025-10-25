<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamImportRequest;
use App\Models\Team;
use App\Services\TeamImportService;

class TeamImportController extends Controller
{
    public function __invoke(TeamImportRequest $request, Team $team, TeamImportService $service)
    {
        $service->import($request, $team);

        return response()->success([
            'message' => trans('global.imported', ['attribute' => trans('team.current_team')]),
        ]);
    }
}
