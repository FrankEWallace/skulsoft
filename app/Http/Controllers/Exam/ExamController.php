<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ExamRequest;
use App\Http\Resources\Exam\ExamResource;
use App\Models\Exam\Exam;
use App\Services\Exam\ExamListService;
use App\Services\Exam\ExamService;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, ExamService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, ExamListService $service)
    {
        return $service->paginate($request);
    }

    public function store(ExamRequest $request, ExamService $service)
    {
        $exam = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('exam.exam')]),
            'exam' => ExamResource::make($exam),
        ]);
    }

    public function show(Exam $exam, ExamService $service)
    {
        $exam->load('term');

        return ExamResource::make($exam);
    }

    public function update(ExamRequest $request, Exam $exam, ExamService $service)
    {
        $service->update($request, $exam);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.exam')]),
        ]);
    }

    public function destroy(Exam $exam, ExamService $service)
    {
        $service->deletable($exam);

        $exam->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.exam')]),
        ]);
    }
}
