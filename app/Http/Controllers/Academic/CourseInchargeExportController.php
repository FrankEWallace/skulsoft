<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\CourseInchargeListService;
use Illuminate\Http\Request;

class CourseInchargeExportController extends Controller
{
    public function __invoke(Request $request, CourseInchargeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
