<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\CommentRequest;
use App\Services\Exam\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exam-schedule:read')->only(['preRequisite', 'fetch']);
        $this->middleware('permission:exam:marks-record|exam:subject-incharge-wise-marks-record')->only(['store', 'remove']);
    }

    public function preRequisite(Request $request, CommentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, CommentService $service)
    {
        return $service->fetch($request);
    }

    public function store(CommentRequest $request, CommentService $service)
    {
        $service->store($request);

        return response()->success([
            'message' => trans('global.stored', ['attribute' => trans('exam.comment')]),
        ]);
    }

    public function remove(Request $request, CommentService $service)
    {
        $service->remove($request);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('exam.comment')]),
        ]);
    }
}
