<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\EditRequestListService;
use Illuminate\Http\Request;

class EditRequestExportController extends Controller
{
    public function __invoke(Request $request, EditRequestListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
