<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Services\Reception\EnquiryListService;
use Illuminate\Http\Request;

class EnquiryExportController extends Controller
{
    public function __invoke(Request $request, EnquiryListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
