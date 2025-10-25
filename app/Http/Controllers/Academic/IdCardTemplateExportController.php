<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\IdCardTemplateListService;
use Illuminate\Http\Request;

class IdCardTemplateExportController extends Controller
{
    public function __invoke(Request $request, IdCardTemplateListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
