<?php

namespace App\Http\Controllers\Resource;

use App\Actions\UpdateViewLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\AssignmentRequest;
use App\Http\Resources\Resource\AssignmentResource;
use App\Models\Resource\Assignment;
use App\Services\Resource\AssignmentListService;
use App\Services\Resource\AssignmentService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, AssignmentService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, AssignmentListService $service)
    {
        $this->authorize('viewAny', Assignment::class);

        return $service->paginate($request);
    }

    public function store(AssignmentRequest $request, AssignmentService $service)
    {
        $this->authorize('create', Assignment::class);

        $assignment = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('resource.assignment.assignment')]),
            'assignment' => AssignmentResource::make($assignment),
        ]);
    }

    public function show(Request $request, string $assignment, AssignmentService $service)
    {
        $assignment = Assignment::findByUuidOrFail($assignment);

        $this->authorize('view', $assignment);

        (new UpdateViewLog)->handle($assignment);

        if (auth()->user()->can('assignment:view-log')) {
            $assignment->load('viewLogs');
        }

        $request->merge([
            'show_details' => true,
        ]);

        $assignment->load(['records.subject', 'records.batch.course', 'type', 'employee' => fn ($q) => $q->summary(), 'media']);

        return AssignmentResource::make($assignment);
    }

    public function update(AssignmentRequest $request, string $assignment, AssignmentService $service)
    {
        $assignment = Assignment::findByUuidOrFail($assignment);

        $this->authorize('update', $assignment);

        $service->update($request, $assignment);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('resource.assignment.assignment')]),
        ]);
    }

    public function destroy(string $assignment, AssignmentService $service)
    {
        $assignment = Assignment::findByUuidOrFail($assignment);

        $this->authorize('delete', $assignment);

        $service->deletable($assignment);

        $assignment->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('resource.assignment.assignment')]),
        ]);
    }

    public function downloadMedia(string $assignment, string $uuid, AssignmentService $service)
    {
        $assignment = Assignment::findByUuidOrFail($assignment);

        $this->authorize('view', $assignment);

        return $assignment->downloadMedia($uuid);
    }
}
