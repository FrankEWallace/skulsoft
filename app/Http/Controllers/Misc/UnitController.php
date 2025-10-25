<?php

namespace App\Http\Controllers\Misc;

use App\Http\Controllers\Controller;
use App\Services\Misc\UnitService;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function searchUnit(Request $request, UnitService $service)
    {
        return response()->json([
            'results' => $service->searchUnit($request->get('query')),
        ]);
    }
}