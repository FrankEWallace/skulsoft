<?php

namespace App\Http\Controllers\Library\Report;

use App\Http\Controllers\Controller;
use App\Services\Library\Report\TopBorrowerListService;
use Illuminate\Http\Request;

class TopBorrowerExportController extends Controller
{
    public function __invoke(Request $request, TopBorrowerListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
