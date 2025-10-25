<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\AssessmentRequest;
use App\Http\Resources\Exam\AssessmentResource;
use App\Models\Exam\Assessment;
use App\Services\Exam\AssessmentListService;
use App\Services\Exam\AssessmentService;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, AssessmentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, AssessmentListService $service)
    {
        return $service->paginate($request);
    }

    public function store(AssessmentRequest $request, AssessmentService $service)
    {
        $assessment = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('exam.assessment.assessment')]),
            'assessment' => AssessmentResource::make($assessment),
        ]);
    }

    public function show(Assessment $assessment, AssessmentService $service)
    {
        return AssessmentResource::make($assessment);
    }

    public function update(AssessmentRequest $request, Assessment $assessment, AssessmentService $service)
    {
        $service->update($request, $assessment);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.assessment.assessment')]),
        ]);
    }

    public function destroy(Assessment $assessment, AssessmentService $service)
    {
        $service->deletable($assessment);

        $assessment->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.assessment.assessment')]),
        ]);
    }
}
