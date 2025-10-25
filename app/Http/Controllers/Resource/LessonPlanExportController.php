<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Services\Resource\LessonPlanListService;
use Illuminate\Http\Request;

class LessonPlanExportController extends Controller
{
    public function __invoke(Request $request, LessonPlanListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
