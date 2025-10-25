<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Schedule;
use App\Services\Exam\ScheduleActionService;
use Illuminate\Http\Request;

class ScheduleActionController extends Controller
{
    public function copyToCourse(Request $request, Schedule $schedule, ScheduleActionService $service)
    {
        $this->authorize('update', $schedule);

        $service->copyToCourse($request, $schedule);

        return response()->success([
            'message' => trans('global.copied', ['attribute' => trans('exam.schedule.schedule')]),
        ]);
    }

    public function updateForm(Request $request, Schedule $schedule, ScheduleActionService $service)
    {
        $this->authorize('update', $schedule);

        $service->updateForm($request, $schedule);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.schedule.schedule')]),
        ]);
    }

    public function togglePublishAdmitCard(Request $request, Schedule $schedule, ScheduleActionService $service)
    {
        $this->authorize('update', $schedule);

        $service->togglePublishAdmitCard($request, $schedule);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.schedule.schedule')]),
        ]);
    }

    public function confirmForm(Request $request, Schedule $schedule, ScheduleActionService $service)
    {
        $this->authorize('confirmForm', $schedule);

        $service->confirmForm($request, $schedule);

        return response()->success(['message' => trans('exam.form.confirmed')]
        );
    }

    public function submitForm(Request $request, Schedule $schedule, ScheduleActionService $service)
    {
        $this->authorize('submitForm', $schedule);

        $service->submitForm($request, $schedule);

        return response()->success(['message' => trans('exam.form.submitted')]
        );
    }
}
