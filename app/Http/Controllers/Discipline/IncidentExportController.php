<?php

namespace App\Http\Controllers\Discipline;

use App\Http\Controllers\Controller;
use App\Services\Discipline\IncidentListService;
use Illuminate\Http\Request;

class IncidentExportController extends Controller
{
    public function __invoke(Request $request, IncidentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
