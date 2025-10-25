<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\OnlineExam;
use App\Services\Exam\OnlineExamQuestionActionService;
use Illuminate\Http\Request;

class OnlineExamQuestionActionController extends Controller
{
    public function reorder(Request $request, string $onlineExam, OnlineExamQuestionActionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('view', $onlineExam);

        $service->reorder($request, $onlineExam);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.online_exam.question.question')]),
        ]);
    }
}
