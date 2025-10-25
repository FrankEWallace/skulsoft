<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\ProgramInchargeListService;
use Illuminate\Http\Request;

class ProgramInchargeExportController extends Controller
{
    public function __invoke(Request $request, ProgramInchargeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
