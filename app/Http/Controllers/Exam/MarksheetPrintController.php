<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\MarksheetPrintService;
use Illuminate\Http\Request;

class MarksheetPrintController extends Controller
{
    public function __construct() {}

    public function preRequisite(Request $request, MarksheetPrintService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function print(Request $request, MarksheetPrintService $service)
    {
        return $service->print($request);
    }

    public function export(Request $request, string $uuid, MarksheetPrintService $service)
    {
        return $service->export($request, $uuid);
    }
}
