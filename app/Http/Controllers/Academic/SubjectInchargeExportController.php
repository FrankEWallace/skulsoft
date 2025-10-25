<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\SubjectInchargeListService;
use Illuminate\Http\Request;

class SubjectInchargeExportController extends Controller
{
    public function __invoke(Request $request, SubjectInchargeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
