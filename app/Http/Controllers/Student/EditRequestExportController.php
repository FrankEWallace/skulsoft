<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\EditRequestListService;
use Illuminate\Http\Request;

class EditRequestExportController extends Controller
{
    public function __invoke(Request $request, EditRequestListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
