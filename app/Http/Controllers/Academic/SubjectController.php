<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\SubjectRequest;
use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\Subject;
use App\Services\Academic\SubjectListService;
use App\Services\Academic\SubjectService;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function preRequisite(SubjectService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, SubjectListService $service)
    {
        $this->authorize('viewAny', Subject::class);

        return $service->paginate($request);
    }

    public function store(SubjectRequest $request, SubjectService $service)
    {
        $this->authorize('create', Subject::class);

        $subject = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.subject.subject')]),
            'subject' => SubjectResource::make($subject),
        ]);
    }

    public function show(string $subject, SubjectService $service): SubjectResource
    {
        $subject = $service->findByUuidOrFail($subject);

        $this->authorize('view', $subject);

        $subject->load('type');

        return SubjectResource::make($subject);
    }

    public function update(SubjectRequest $request, string $subject, SubjectService $service)
    {
        $subject = $service->findByUuidOrFail($subject);

        $this->authorize('update', $subject);

        $service->update($request, $subject);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.subject.subject')]),
        ]);
    }

    public function destroy(string $subject, SubjectService $service)
    {
        $subject = $service->findByUuidOrFail($subject);

        $this->authorize('delete', $subject);

        $service->deletable($subject);

        $subject->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.subject.subject')]),
        ]);
    }
}
