<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\AssignmentEvaluationRequest;
use App\Models\Resource\Assignment;
use App\Services\Resource\AssignmentEvaluationService;

class AssignmentEvaluationController extends Controller
{
    public function __construct()
    {
        //
    }

    public function store(AssignmentEvaluationRequest $request, string $assignment, AssignmentEvaluationService $service)
    {
        $assignment = Assignment::findByUuidOrFail($assignment);

        $service->evaluate($request, $assignment);

        return response()->success([
            'message' => trans('global.submitted', ['attribute' => trans('resource.assignment.assignment')]),
        ]);
    }
}
