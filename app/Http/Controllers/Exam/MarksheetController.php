<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\MarksheetService;
use Illuminate\Http\Request;

class MarksheetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exam-marksheet:access')->only(['preRequisite', 'fetchReport']);
    }

    public function preRequisite(Request $request, MarksheetService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetchReport(Request $request, MarksheetService $service)
    {
        return $service->fetchReport($request);
    }
}
