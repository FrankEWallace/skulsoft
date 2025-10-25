<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\SubjectListService;
use Illuminate\Http\Request;

class SubjectExportController extends Controller
{
    public function __invoke(Request $request, SubjectListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
