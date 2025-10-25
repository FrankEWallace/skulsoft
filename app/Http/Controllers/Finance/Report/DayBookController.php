<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\DayBookListService;
use App\Services\Finance\Report\DayBookService;
use Illuminate\Http\Request;

class DayBookController extends Controller
{
    public function preRequisite(Request $request, DayBookService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, DayBookListService $service)
    {
        return $service->paginate($request);
    }
}
