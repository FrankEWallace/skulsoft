<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\EnrollmentSeatListService;
use Illuminate\Http\Request;

class EnrollmentSeatExportController extends Controller
{
    public function __invoke(Request $request, EnrollmentSeatListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
