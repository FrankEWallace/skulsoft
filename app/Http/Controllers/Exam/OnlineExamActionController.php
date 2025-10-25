<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\OnlineExam;
use App\Services\Exam\OnlineExamActionService;
use Illuminate\Http\Request;

class OnlineExamActionController extends Controller
{
    public function updateStatus(Request $request, string $onlineExam, OnlineExamActionService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('update', $onlineExam);

        $service->updateStatus($request, $onlineExam);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.online_exam.online_exam')]),
        ]);
    }
}
