<?php

namespace App\Http\Controllers\Resource\Report;

use App\Http\Controllers\Controller;
use App\Services\Resource\Report\DateWiseStudentDiaryService;
use Illuminate\Http\Request;

class DateWiseStudentDiaryController extends Controller
{
    public function preRequisite(Request $request, DateWiseStudentDiaryService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, DateWiseStudentDiaryService $service)
    {
        return $service->generate($request);
    }

    public function export(Request $request, DateWiseStudentDiaryService $service)
    {
        return $service->generate($request);
    }
}
