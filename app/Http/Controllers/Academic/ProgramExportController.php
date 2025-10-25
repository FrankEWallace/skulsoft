<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\ProgramListService;
use Illuminate\Http\Request;

class ProgramExportController extends Controller
{
    public function __invoke(Request $request, ProgramListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
