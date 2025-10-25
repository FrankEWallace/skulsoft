<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Services\Library\BookLabelService;
use Illuminate\Http\Request;

class BookLabelController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, BookLabelService $service)
    {
        return $service->preRequisite($request);
    }

    public function print(Request $request, BookLabelService $service)
    {
        return $service->print($request);
    }
}
