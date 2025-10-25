<?php

namespace App\Http\Controllers;

use App\Services\OptionActionService;
use Illuminate\Http\Request;

class OptionActionController extends Controller
{
    public function reorder(Request $request, OptionActionService $service)
    {
        $service->reorder($request);

        return response()->ok([]);
    }
}
