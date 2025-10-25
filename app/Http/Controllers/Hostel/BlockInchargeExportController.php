<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Services\Hostel\BlockInchargeListService;
use Illuminate\Http\Request;

class BlockInchargeExportController extends Controller
{
    public function __invoke(Request $request, BlockInchargeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
