<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Team\RoleListService;
use Illuminate\Http\Request;

class RoleExportController extends Controller
{
    public function __invoke(Request $request, Team $team, RoleListService $service)
    {
        $list = $service->list($request, $team);

        return $service->export($list);
    }
}
