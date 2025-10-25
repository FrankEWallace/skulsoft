<?php

namespace App\Http\Controllers;

use App\Services\SetupService;
use Illuminate\Http\Request;

class SetupController extends Controller
{
    public function __invoke(Request $request, SetupService $service)
    {
        return $service->handle();
    }
}
