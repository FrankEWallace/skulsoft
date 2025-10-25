<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Services\Communication\AnnouncementListService;
use Illuminate\Http\Request;

class AnnouncementExportController extends Controller
{
    public function __invoke(Request $request, AnnouncementListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
