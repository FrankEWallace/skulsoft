<?php

namespace App\Http\Controllers\Recruitment\Job;

use App\Services\Recruitment\Job\VacancyService;
use Illuminate\Http\Request;

class VacancyController
{
    public function preRequisite(Request $request, VacancyService $service)
    {
        return $service->preRequisite($request);
    }

    public function list(Request $request, VacancyService $service)
    {
        return $service->list($request);
    }

    public function detail(Request $request, string $slug, VacancyService $service)
    {
        return $service->detail($request, $slug);
    }
}
