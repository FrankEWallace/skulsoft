<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Services\Recruitment\ApplicationListService;
use Illuminate\Http\Request;

class ApplicationExportController extends Controller
{
    public function __invoke(Request $request, ApplicationListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
