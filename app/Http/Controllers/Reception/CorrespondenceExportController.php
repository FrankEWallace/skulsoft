<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Services\Reception\CorrespondenceListService;
use Illuminate\Http\Request;

class CorrespondenceExportController extends Controller
{
    public function __invoke(Request $request, CorrespondenceListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
