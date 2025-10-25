<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\TransferRequestListService;
use Illuminate\Http\Request;

class TransferRequestExportController extends Controller
{
    public function __invoke(Request $request, TransferRequestListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
