<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Services\Utility\TodoListService;
use Illuminate\Http\Request;

class TodoExportController extends Controller
{
    public function __invoke(Request $request, TodoListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
