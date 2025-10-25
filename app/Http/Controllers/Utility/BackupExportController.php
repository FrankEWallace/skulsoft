<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Services\Utility\BackupListService;
use Illuminate\Http\Request;

class BackupExportController extends Controller
{
    public function __invoke(Request $request, BackupListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
