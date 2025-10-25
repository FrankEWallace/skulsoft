<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\RegistrationListService;
use Illuminate\Http\Request;

class RegistrationExportController extends Controller
{
    public function __invoke(Request $request, RegistrationListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
