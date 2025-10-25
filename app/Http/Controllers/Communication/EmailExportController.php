<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Services\Communication\EmailListService;
use Illuminate\Http\Request;

class EmailExportController extends Controller
{
    public function __invoke(Request $request, EmailListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
