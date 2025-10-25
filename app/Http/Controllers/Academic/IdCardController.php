<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\IdCardService;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, IdCardService $service)
    {
        return $service->preRequisite($request);
    }

    public function print(Request $request, IdCardService $service)
    {
        return $service->print($request);
    }
}
