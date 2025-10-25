<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\DivisionListService;
use Illuminate\Http\Request;

class DivisionExportController extends Controller
{
    public function __invoke(Request $request, DivisionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
