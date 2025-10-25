<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Services\Resource\LearningMaterialListService;
use Illuminate\Http\Request;

class LearningMaterialExportController extends Controller
{
    public function __invoke(Request $request, LearningMaterialListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
