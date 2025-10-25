<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Calendar\HolidayListService;
use Illuminate\Http\Request;

class HolidayExportController extends Controller
{
    public function __invoke(Request $request, HolidayListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
