<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\ObservationListService;
use Illuminate\Http\Request;

class ObservationExportController extends Controller
{
    public function __invoke(Request $request, ObservationListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
