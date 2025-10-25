<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Services\Communication\SMSListService;
use Illuminate\Http\Request;

class SMSExportController extends Controller
{
    public function __invoke(Request $request, SMSListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
