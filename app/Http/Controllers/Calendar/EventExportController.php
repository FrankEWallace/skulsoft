<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Calendar\EventListService;
use Illuminate\Http\Request;

class EventExportController extends Controller
{
    public function __invoke(Request $request, EventListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
