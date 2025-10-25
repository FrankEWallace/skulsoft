<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Services\Mess\MealListService;
use Illuminate\Http\Request;

class MealExportController extends Controller
{
    public function __invoke(Request $request, MealListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
