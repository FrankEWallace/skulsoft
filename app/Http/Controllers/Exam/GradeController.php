<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\GradeRequest;
use App\Http\Resources\Exam\GradeResource;
use App\Models\Exam\Grade;
use App\Services\Exam\GradeListService;
use App\Services\Exam\GradeService;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, GradeService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, GradeListService $service)
    {
        return $service->paginate($request);
    }

    public function store(GradeRequest $request, GradeService $service)
    {
        $grade = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('exam.grade.grade')]),
            'grade' => GradeResource::make($grade),
        ]);
    }

    public function show(Grade $grade, GradeService $service)
    {
        return GradeResource::make($grade);
    }

    public function update(GradeRequest $request, Grade $grade, GradeService $service)
    {
        $service->update($request, $grade);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.grade.grade')]),
        ]);
    }

    public function destroy(Grade $grade, GradeService $service)
    {
        $service->deletable($grade);

        $grade->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.grade.grade')]),
        ]);
    }
}
