<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Services\Hostel\RoomListService;
use Illuminate\Http\Request;

class RoomExportController extends Controller
{
    public function __invoke(Request $request, RoomListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
