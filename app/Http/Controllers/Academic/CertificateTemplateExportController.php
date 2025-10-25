<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\CertificateTemplateListService;
use Illuminate\Http\Request;

class CertificateTemplateExportController extends Controller
{
    public function __invoke(Request $request, CertificateTemplateListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
