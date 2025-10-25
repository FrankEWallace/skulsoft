<?php

namespace App\Http\Controllers\Misc;

use App\Http\Controllers\Controller;
use App\Services\Misc\InstituteService;
use Illuminate\Http\Request;

class InstituteController extends Controller
{
    public function searchInstitute(Request $request, InstituteService $service)
    {
        return response()->json([
            'results' => $service->searchInstitute($request->get('query')),
        ]);
    }

    public function searchAffiliationBody(Request $request, InstituteService $service)
    {
        return response()->json([
            'results' => $service->searchAffiliationBody($request->get('query')),
        ]);
    }
}
