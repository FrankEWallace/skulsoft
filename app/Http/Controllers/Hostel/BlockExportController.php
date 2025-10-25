<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Services\Hostel\BlockListService;
use Illuminate\Http\Request;

class BlockExportController extends Controller
{
    public function __invoke(Request $request, BlockListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
