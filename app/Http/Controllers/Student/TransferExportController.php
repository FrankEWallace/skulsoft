<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\TransferListService;
use Illuminate\Http\Request;

class TransferExportController extends Controller
{
    public function __invoke(Request $request, TransferListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
