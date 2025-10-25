<?php

namespace App\Http\Controllers;

use App\Services\CustomFieldListService;
use Illuminate\Http\Request;

class CustomFieldExportController extends Controller
{
    public function __invoke(Request $request, CustomFieldListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
