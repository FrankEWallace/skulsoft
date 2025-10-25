<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\SubjectInchargeRequest;
use App\Http\Resources\Academic\SubjectInchargeResource;
use App\Models\Incharge;
use App\Services\Academic\SubjectInchargeListService;
use App\Services\Academic\SubjectInchargeService;
use Illuminate\Http\Request;

class SubjectInchargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, SubjectInchargeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, SubjectInchargeListService $service)
    {
        $this->authorize('viewAny', Incharge::class, 'subject');

        return $service->paginate($request);
    }

    public function store(SubjectInchargeRequest $request, SubjectInchargeService $service)
    {
        $this->authorize('create', Incharge::class, 'subject');

        $subjectIncharge = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.subject_incharge.subject_incharge')]),
            'subject_incharge' => SubjectInchargeResource::make($subjectIncharge),
        ]);
    }

    public function show(Request $request, string $subjectIncharge, SubjectInchargeService $service)
    {
        $subjectIncharge = Incharge::findByUuidOrFail($subjectIncharge);

        $this->authorize('view', $subjectIncharge, 'subject');

        $subjectIncharge->load('model', 'detail.course');

        return SubjectInchargeResource::make($subjectIncharge);
    }

    public function update(SubjectInchargeRequest $request, string $subjectIncharge, SubjectInchargeService $service)
    {
        $subjectIncharge = Incharge::findByUuidOrFail($subjectIncharge);

        $this->authorize('update', $subjectIncharge);

        $service->update($request, $subjectIncharge, 'subject');

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.subject_incharge.subject_incharge')]),
        ]);
    }

    public function destroy(string $subjectIncharge, SubjectInchargeService $service)
    {
        $subjectIncharge = Incharge::findByUuidOrFail($subjectIncharge);

        $this->authorize('delete', $subjectIncharge, 'subject');

        $service->deletable($subjectIncharge);

        $subjectIncharge->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.subject_incharge.subject_incharge')]),
        ]);
    }
}
