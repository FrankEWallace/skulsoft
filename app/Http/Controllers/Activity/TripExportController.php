<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Services\Activity\TripListService;
use Illuminate\Http\Request;

class TripExportController extends Controller
{
    public function __invoke(Request $request, TripListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
