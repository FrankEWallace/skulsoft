<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\CourseListService;
use Illuminate\Http\Request;

class CourseExportController extends Controller
{
    public function __invoke(Request $request, CourseListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
