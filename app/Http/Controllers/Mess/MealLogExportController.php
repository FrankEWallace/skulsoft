<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Services\Mess\MealLogListService;
use Illuminate\Http\Request;

class MealLogExportController extends Controller
{
    public function __invoke(Request $request, MealLogListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
