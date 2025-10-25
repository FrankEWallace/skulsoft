<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\AnonymousPaymentService;
use Illuminate\Http\Request;

class AnonymousPaymentController extends Controller
{
    public function getFeeDetail(Request $request, AnonymousPaymentService $service)
    {
        return response()->ok($service->getDetail($request));
    }
}
