<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\OnlineExam;
use App\Services\Exam\OnlineExamQuestionListService;
use Illuminate\Http\Request;

class OnlineExamQuestionExportController extends Controller
{
    public function __invoke(Request $request, string $onlineExam, OnlineExamQuestionListService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $list = $service->list($request, $onlineExam);

        return $service->export($list);
    }
}
