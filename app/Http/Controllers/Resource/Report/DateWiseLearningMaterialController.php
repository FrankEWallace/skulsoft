<?php

namespace App\Http\Controllers\Resource\Report;

use App\Http\Controllers\Controller;
use App\Services\Resource\Report\DateWiseLearningMaterialService;
use Illuminate\Http\Request;

class DateWiseLearningMaterialController extends Controller
{
    public function preRequisite(Request $request, DateWiseLearningMaterialService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, DateWiseLearningMaterialService $service)
    {
        return $service->generate($request);
    }

    public function export(Request $request, DateWiseLearningMaterialService $service)
    {
        return $service->generate($request);
    }
}
