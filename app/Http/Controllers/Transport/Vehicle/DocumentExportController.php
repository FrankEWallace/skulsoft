<?php

namespace App\Http\Controllers\Transport\Vehicle;

use App\Http\Controllers\Controller;
use App\Services\Transport\Vehicle\DocumentListService;
use Illuminate\Http\Request;

class DocumentExportController extends Controller
{
    public function __invoke(Request $request, DocumentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
