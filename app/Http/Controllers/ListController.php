<?php

namespace App\Http\Controllers;

use App\Services\ListService;
use Illuminate\Http\Request;

class ListController extends Controller
{
    public function preRequisite(Request $request, ListService $service)
    {
        return $service->preRequisite($request);
    }
}
