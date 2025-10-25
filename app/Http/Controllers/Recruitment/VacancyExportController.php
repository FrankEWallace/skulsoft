<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Services\Recruitment\VacancyListService;
use Illuminate\Http\Request;

class VacancyExportController extends Controller
{
    public function __invoke(Request $request, VacancyListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
