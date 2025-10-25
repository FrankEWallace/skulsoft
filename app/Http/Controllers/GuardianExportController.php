<?php

namespace App\Http\Controllers;

use App\Services\GuardianListService;
use Illuminate\Http\Request;

class GuardianExportController extends Controller
{
    public function __invoke(Request $request, GuardianListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
