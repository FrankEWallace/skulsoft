<?php

namespace App\Http\Controllers;

use App\Services\OptionListService;
use Illuminate\Http\Request;

class OptionExportController extends Controller
{
    public function __invoke(Request $request, OptionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
