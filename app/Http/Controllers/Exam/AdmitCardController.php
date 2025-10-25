<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\AdmitCardService;
use Illuminate\Http\Request;

class AdmitCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exam-admit-card:access')->only(['preRequisite', 'fetch']);
    }

    public function preRequisite(Request $request, AdmitCardService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetchReport(Request $request, AdmitCardService $service)
    {
        return $service->fetchReport($request);
    }
}
