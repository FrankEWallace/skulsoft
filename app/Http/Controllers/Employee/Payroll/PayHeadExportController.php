<?php

namespace App\Http\Controllers\Employee\Payroll;

use App\Http\Controllers\Controller;
use App\Services\Employee\Payroll\PayHeadListService;
use Illuminate\Http\Request;

class PayHeadExportController extends Controller
{
    public function __invoke(Request $request, PayHeadListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
