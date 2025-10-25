<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Resources\Exam\OnlineExamSubmissionQuestionResource;
use App\Models\Exam\OnlineExam;
use App\Services\Exam\OnlineExamSubmissionListService;
use App\Services\Exam\OnlineExamSubmissionService;
use Illuminate\Http\Request;

class OnlineExamSubmissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $onlineExam, OnlineExamSubmissionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        return response()->ok($service->preRequisite($request, $onlineExam));
    }

    public function getQuestions(Request $request, string $onlineExam, string $submission, OnlineExamSubmissionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('view', $onlineExam);

        $submission = $service->getSubmission($request, $onlineExam, $submission);

        $onlineExam->submission = $submission;

        return OnlineExamSubmissionQuestionResource::make($onlineExam);
    }

    public function index(Request $request, string $onlineExam, OnlineExamSubmissionListService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('view', $onlineExam);

        return $service->paginate($request, $onlineExam);
    }

    public function evaluate(Request $request, string $onlineExam, string $submission, OnlineExamSubmissionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('view', $onlineExam);

        $service->evaluate($request, $onlineExam, $submission);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.online_exam.submission.submission')]),
        ]);
    }

    public function destroy(string $onlineExam, string $submission, OnlineExamSubmissionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $submission = $service->findByUuidOrFail($onlineExam, $submission);

        $service->deletable($onlineExam, $submission);

        $submission->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.online_exam.submission.submission')]),
        ]);
    }
}
