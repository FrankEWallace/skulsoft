<?php

namespace App\Http\Controllers\Recruitment\Job;

use App\Http\Requests\Recruitment\Job\ApplicationRequest;
use App\Services\Recruitment\Job\ApplicationService;

class ApplicationController
{
    public function store(ApplicationRequest $request, string $slug, ApplicationService $service)
    {
        if (request()->query('option')) {
            return;
        }

        $service->create($request);

        return response()->success([
            'message' => trans('recruitment.application_submitted'),
        ]);
    }
}
