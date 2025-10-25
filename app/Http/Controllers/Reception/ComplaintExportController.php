<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Services\Reception\ComplaintListService;
use Illuminate\Http\Request;

class ComplaintExportController extends Controller
{
    public function __invoke(Request $request, ComplaintListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
