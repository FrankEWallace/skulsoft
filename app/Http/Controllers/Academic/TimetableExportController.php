<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\TimetableListService;
use Illuminate\Http\Request;

class TimetableExportController extends Controller
{
    public function __invoke(Request $request, TimetableListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
