<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ScheduleRequest;
use App\Http\Resources\Exam\ScheduleFormSubmissionResource;
use App\Http\Resources\Exam\ScheduleResource;
use App\Models\Exam\Schedule;
use App\Services\Exam\ScheduleListService;
use App\Services\Exam\ScheduleService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, ScheduleService $service)
    {
        $this->authorize('preRequisite', Schedule::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, ScheduleListService $service)
    {
        $this->authorize('viewAny', Schedule::class);

        return $service->paginate($request);
    }

    public function store(ScheduleRequest $request, ScheduleService $service)
    {
        $this->authorize('create', Schedule::class);

        $schedule = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('exam.schedule.schedule')]),
            'schedule' => ScheduleResource::make($schedule),
        ]);
    }

    public function show(Request $request, string $schedule, ScheduleService $service)
    {
        $schedule = Schedule::findByUuidOrFail($schedule);

        $this->authorize('view', $schedule);

        $schedule->load('records.subject', 'batch.course', 'exam.term.division', 'grade', 'assessment', 'observation');

        if (auth()->user()->hasRole('student') && $request->query('form_submission')) {
            $schedule = $service->getFormSubmissionData($schedule);

            return ScheduleFormSubmissionResource::make($schedule);
        }

        return ScheduleResource::make($schedule);
    }

    public function update(ScheduleRequest $request, string $schedule, ScheduleService $service)
    {
        $schedule = Schedule::findByUuidOrFail($schedule);

        $this->authorize('update', $schedule);

        $service->update($request, $schedule);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.schedule.schedule')]),
        ]);
    }

    public function destroy(string $schedule, ScheduleService $service)
    {
        $schedule = Schedule::findByUuidOrFail($schedule);

        $this->authorize('delete', $schedule);

        $service->deletable($schedule);

        $service->delete($schedule);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.schedule.schedule')]),
        ]);
    }
}
