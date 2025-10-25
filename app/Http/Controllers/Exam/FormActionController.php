<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Form;
use App\Services\Exam\FormActionService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class FormActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exam-form:manage')->only(['updateStatus']);
    }

    public function updateStatus(Request $request, string $form, FormActionService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $service->updateStatus($request, $form);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.form.form')]),
        ]);
    }

    public function print(Request $request, string $form, FormActionService $service)
    {
        if (! auth()->user()->hasRole('student')) {
            Gate::authorize('exam-form:manage');
        }

        $form = Form::findByUuidOrFail($form);

        $student = $service->getStudentDetail($form->student_id);

        $form->load('schedule.records', 'schedule.exam');

        $schedule = $form->schedule;
        $exam = $schedule->exam;

        $records = $service->getRecords($student, $schedule, $form);

        $layout = [
            'column' => 1,
            'margin_top' => 0,
            'box_width' => '100%',
            'show_sno' => true,
            'show_print_date_time' => true,
            'show_watermark' => true,
            'signatory1' => Arr::get($exam->config_detail, 'signatory1'),
            'signatory2' => Arr::get($exam->config_detail, 'signatory2'),
            'signatory3' => Arr::get($exam->config_detail, 'signatory3'),
            'signatory4' => Arr::get($exam->config_detail, 'signatory4'),
        ];

        return view()->first([config('config.print.custom_path').'exam.exam-form', 'print.exam.exam-form'], compact('student', 'form', 'schedule', 'records', 'layout'))->render();
    }

    public function printAdmitCard(Request $request, string $form, FormActionService $service)
    {
        if (! auth()->user()->hasRole('student')) {
            Gate::authorize('exam-form:manage');
        }

        $form = Form::findByUuidOrFail($form);

        $student = $service->getStudentDetail($form->student_id);

        $form->load('schedule.records', 'schedule.exam');

        $schedule = $form->schedule;
        $exam = $schedule->exam;

        $records = $service->getRecords($student, $schedule, $form);

        $layout = [
            'column' => 1,
            'margin_top' => 0,
            'box_width' => '100%',
            'show_sno' => true,
            'show_print_date_time' => true,
            'show_watermark' => true,
            'signatory1' => Arr::get($exam->config_detail, 'signatory1'),
            'signatory2' => Arr::get($exam->config_detail, 'signatory2'),
            'signatory3' => Arr::get($exam->config_detail, 'signatory3'),
            'signatory4' => Arr::get($exam->config_detail, 'signatory4'),
        ];

        return view()->first([config('config.print.custom_path').'exam.exam-form-admit-card', 'print.exam.exam-form-admit-card'], compact('student', 'form', 'schedule', 'records', 'layout'))->render();
    }
}
