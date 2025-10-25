<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Services\Communication\WhatsAppListService;
use Illuminate\Http\Request;

class WhatsAppExportController extends Controller
{
    public function __invoke(Request $request, WhatsAppListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
