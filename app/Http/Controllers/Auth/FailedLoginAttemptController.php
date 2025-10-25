<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\FailedLoginAttemptListService;
use Illuminate\Http\Request;

class FailedLoginAttemptController extends Controller
{
    public function __invoke(Request $request, FailedLoginAttemptListService $service)
    {
        return $service->paginate($request);
    }
}
