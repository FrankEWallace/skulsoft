<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\OnlineExamRequest;
use App\Http\Resources\Exam\OnlineExamResource;
use App\Models\Exam\OnlineExam;
use App\Models\Student\Student;
use App\Services\Exam\OnlineExamListService;
use App\Services\Exam\OnlineExamService;
use Illuminate\Http\Request;

class OnlineExamController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, OnlineExamService $service)
    {
        $this->authorize('preRequisite', OnlineExam::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, OnlineExamListService $service)
    {
        $this->authorize('viewAny', OnlineExam::class);

        return $service->paginate($request);
    }

    public function store(OnlineExamRequest $request, OnlineExamService $service)
    {
        $this->authorize('create', OnlineExam::class);

        $onlineExam = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('exam.online_exam.online_exam')]),
            'term' => OnlineExamResource::make($onlineExam),
        ]);
    }

    public function show(Request $request, string $onlineExam, OnlineExamService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('view', $onlineExam);

        $request->merge([
            'show_details' => true,
        ]);

        $onlineExam->load(['records.subject', 'records.batch.course', 'employee' => fn ($q) => $q->summary(), 'media']);

        if ($request->boolean('submission') && auth()->user()->hasRole('student')) {
            $student = Student::query()
                ->auth()
                ->first();

            if ($onlineExam->is_live) {
                $onlineExam->load(['submissions' => function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                }]);
            } elseif ($onlineExam->is_completed && $onlineExam->result_published_at->value) {
                $onlineExam->load(['questions', 'submissions' => function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                }]);
            }
        } else {
            $onlineExam->loadCount('questions', 'submissions');
        }

        return OnlineExamResource::make($onlineExam);
    }

    public function update(OnlineExamRequest $request, string $onlineExam, OnlineExamService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('update', $onlineExam);

        $service->update($request, $onlineExam);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.online_exam.online_exam')]),
        ]);
    }

    public function destroy(string $onlineExam, OnlineExamService $service)
    {
        $onlineExam = OnlineExam::findByUuidOrFail($onlineExam);

        $this->authorize('delete', $onlineExam);

        $service->deletable($onlineExam);

        $onlineExam->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.online_exam.online_exam')]),
        ]);
    }
}
