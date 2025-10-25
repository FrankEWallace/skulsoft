<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\ClassTimingListService;
use Illuminate\Http\Request;

class ClassTimingExportController extends Controller
{
    public function __invoke(Request $request, ClassTimingListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
