<?php

namespace App\Http\Controllers;

use App\Services\TeamListService;
use Illuminate\Http\Request;

class TeamExportController extends Controller
{
    public function __invoke(Request $request, TeamListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
