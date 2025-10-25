<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Resources\Exam\OnlineExamLiveQuestionResource;
use App\Models\Exam\OnlineExam;
use App\Services\Exam\OnlineExamSubmitService;
use Illuminate\Http\Request;

class OnlineExamSubmitController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:student');
    }

    public function getQuestions(Request $request, string $onlineExam, OnlineExamSubmitService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $onlineExam = $service->getQuestions($onlineExam);

        return OnlineExamLiveQuestionResource::make($onlineExam);
    }

    public function startSubmission(Request $request, string $onlineExam, OnlineExamSubmitService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $startedAt = $service->startSubmission($request, $onlineExam);

        return response()->success([
            'started_at' => $startedAt,
        ]);
    }

    public function submit(Request $request, string $onlineExam, OnlineExamSubmitService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $submittedAnswerCount = $service->store($request, $onlineExam);

        return response()->success([
            'submitted_answer_count' => $submittedAnswerCount,
        ]);
    }

    public function finishSubmission(Request $request, string $onlineExam, OnlineExamSubmitService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $submittedAt = $service->finishSubmission($request, $onlineExam);

        return response()->success([
            'message' => trans('global.submitted', ['attribute' => trans('exam.online_exam.props.answer')]),
            'submitted_at' => $submittedAt,
        ]);
    }
}
