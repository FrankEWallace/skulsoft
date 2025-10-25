<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\AssignmentSubmissionRequest;
use App\Models\Resource\Assignment;
use App\Models\Resource\AssignmentSubmission;
use App\Services\Resource\AssignmentSubmissionService;
use Illuminate\Http\Request;

class AssignmentSubmissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:student')->only('store');
    }

    public function index(Request $request, string $assignment, AssignmentSubmissionService $service)
    {
        $assignment = Assignment::findByUuidOrFail($assignment);

        return $service->getSubmissions($request, $assignment);
    }

    public function store(AssignmentSubmissionRequest $request, string $assignment, AssignmentSubmissionService $service)
    {
        $assignment = Assignment::findByUuidOrFail($assignment);

        $service->submit($request, $assignment);

        return response()->success([
            'message' => trans('global.submitted', ['attribute' => trans('resource.assignment.assignment')]),
        ]);
    }

    public function downloadMedia(string $assignment, string $submission, string $uuid, AssignmentSubmissionService $service)
    {
        $assignment = Assignment::findByUuidOrFail($assignment);

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('uuid', $submission)
            ->firstOrFail();

        return $submission->downloadMedia($uuid);
    }
}
