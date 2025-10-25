<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\ProgramTypeListService;
use Illuminate\Http\Request;

class ProgramTypeExportController extends Controller
{
    public function __invoke(Request $request, ProgramTypeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
