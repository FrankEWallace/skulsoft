<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Calendar\CelebrationListService;
use Illuminate\Http\Request;

class CelebrationExportController extends Controller
{
    public function __invoke(Request $request, CelebrationListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
