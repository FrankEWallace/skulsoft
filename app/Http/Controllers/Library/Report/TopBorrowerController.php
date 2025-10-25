<?php

namespace App\Http\Controllers\Library\Report;

use App\Http\Controllers\Controller;
use App\Services\Library\Report\TopBorrowerListService;
use App\Services\Library\Report\TopBorrowerService;
use Illuminate\Http\Request;

class TopBorrowerController extends Controller
{
    public function preRequisite(Request $request, TopBorrowerService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, TopBorrowerListService $service)
    {
        return $service->paginate($request);
    }
}
