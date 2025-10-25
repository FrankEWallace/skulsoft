<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\SubjectService;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function preRequisite(Request $request, SubjectService $service)
    {
        $this->authorize('manageRecord', Student::class);

        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, SubjectService $service)
    {
        $this->authorize('manageRecord', Student::class);

        return $service->fetch($request);
    }

    public function store(Request $request, SubjectService $service)
    {
        $this->authorize('manageRecord', Student::class);

        $service->store($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.subject.subject')]),
        ]);
    }
}
