<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Exam;
use App\Services\Exam\ExamActionService;
use Illuminate\Http\Request;

class ExamActionController extends Controller
{
    public function storeConfig(Request $request, Exam $exam, ExamActionService $service)
    {
        $service->storeConfig($request, $exam);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.exam')]),
        ]);
    }

    public function reorder(Request $request, ExamActionService $service)
    {
        $this->authorize('create', Exam::class);

        $menu = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.exam')]),
        ]);
    }
}
