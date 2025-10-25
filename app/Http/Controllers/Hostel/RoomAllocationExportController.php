<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Services\Hostel\RoomAllocationListService;
use Illuminate\Http\Request;

class RoomAllocationExportController extends Controller
{
    public function __invoke(Request $request, RoomAllocationListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
