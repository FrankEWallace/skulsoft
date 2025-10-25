<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\SessionListService;
use Illuminate\Http\Request;

class SessionExportController extends Controller
{
    public function __invoke(Request $request, SessionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
