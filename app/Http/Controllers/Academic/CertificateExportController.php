<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\CertificateListService;
use Illuminate\Http\Request;

class CertificateExportController extends Controller
{
    public function __invoke(Request $request, CertificateListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
