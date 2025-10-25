<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\OnlineExamQuestionRequest;
use App\Http\Resources\Exam\OnlineExamQuestionResource;
use App\Models\Exam\OnlineExam;
use App\Services\Exam\OnlineExamQuestionListService;
use App\Services\Exam\OnlineExamQuestionService;
use Illuminate\Http\Request;

class OnlineExamQuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $onlineExam, OnlineExamQuestionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        return response()->ok($service->preRequisite($request, $onlineExam));
    }

    public function index(Request $request, string $onlineExam, OnlineExamQuestionListService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('view', $onlineExam);

        return $service->paginate($request, $onlineExam);
    }

    public function store(OnlineExamQuestionRequest $request, string $onlineExam, OnlineExamQuestionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $question = $service->create($request, $onlineExam);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('exam.online_exam.question.question')]),
            'question' => OnlineExamQuestionResource::make($question),
        ]);
    }

    public function show(Request $request, string $onlineExam, string $question, OnlineExamQuestionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('view', $onlineExam);

        $question = $service->findByUuidOrFail($onlineExam, $question);

        return OnlineExamQuestionResource::make($question);
    }

    public function update(OnlineExamQuestionRequest $request, string $onlineExam, string $question, OnlineExamQuestionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $question = $service->findByUuidOrFail($onlineExam, $question);

        $service->update($request, $onlineExam, $question);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.online_exam.question.question')]),
        ]);
    }

    public function destroy(string $onlineExam, string $question, OnlineExamQuestionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $question = $service->findByUuidOrFail($onlineExam, $question);

        $service->deletable($onlineExam, $question);

        $question->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.online_exam.question.question')]),
        ]);
    }
}
