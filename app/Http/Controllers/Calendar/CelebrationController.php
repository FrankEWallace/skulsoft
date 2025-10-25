<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Calendar\CelebrationListService;
use App\Services\Calendar\CelebrationService;
use Illuminate\Http\Request;

class CelebrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:celebration:read');
    }

    public function preRequisite(Request $request, CelebrationService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, CelebrationListService $service)
    {
        return $service->paginate($request);
    }
}
