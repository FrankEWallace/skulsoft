<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ExamActionService
{
    public function storeConfig(Request $request, Exam $exam): void
    {
        $request->validate([
            'exam_form_fee' => 'sometimes|required|numeric|min:0',
            'exam_form_late_fee' => 'sometimes|required|numeric|min:0',
            'exam_form_last_date' => 'sometimes|nullable|date_format:Y-m-d',
            'show_sno' => 'required|boolean',
            'show_print_date_time' => 'required|boolean',
            'show_watermark' => 'required|boolean',
            'info' => 'nullable|string|max:1000',
            'signatory1' => 'nullable|max:50',
            'signatory2' => 'nullable|max:50',
            'signatory3' => 'nullable|max:50',
            'signatory4' => 'nullable|max:50',
            'first_attempt.title' => 'sometimes|nullable|string|max:100',
            'first_attempt.sub_title' => 'sometimes|nullable|string|max:100',
            'first_attempt.publish_marksheet' => 'sometimes|boolean',
            'second_attempt.title' => 'sometimes|nullable|string|max:100',
            'second_attempt.sub_title' => 'sometimes|nullable|string|max:100',
            'second_attempt.publish_marksheet' => 'sometimes|boolean',
            'third_attempt.title' => 'sometimes|nullable|string|max:100',
            'third_attempt.sub_title' => 'sometimes|nullable|string|max:100',
            'third_attempt.publish_marksheet' => 'sometimes|boolean',
            'fourth_attempt.title' => 'sometimes|nullable|string|max:100',
            'fourth_attempt.sub_title' => 'sometimes|nullable|string|max:100',
            'fourth_attempt.publish_marksheet' => 'sometimes|boolean',
            'fifth_attempt.title' => 'sometimes|nullable|string|max:100',
            'fifth_attempt.sub_title' => 'sometimes|nullable|string|max:100',
            'fifth_attempt.publish_marksheet' => 'sometimes|boolean',
        ]);

        if ($request->exam_form_fee > 0 || $request->exam_form_late_fee > 0) {
            $request->validate([
                'exam_form_last_date' => 'required',
            ]);
        }

        $config = $exam->config;
        $config['exam_form_fee'] = $request->exam_form_fee;
        $config['exam_form_late_fee'] = $request->exam_form_late_fee;
        $config['exam_form_last_date'] = $request->exam_form_last_date;
        $config['title'] = $request->title;
        $config['show_sno'] = $request->boolean('show_sno');
        $config['show_print_date_time'] = $request->boolean('show_print_date_time');
        $config['show_watermark'] = $request->boolean('show_watermark');
        $config['info'] = $request->info;
        $config['signatory1'] = $request->signatory1;
        $config['signatory2'] = $request->signatory2;
        $config['signatory3'] = $request->signatory3;
        $config['signatory4'] = $request->signatory4;
        $config['first_attempt'] = [
            'title' => $request->input('first_attempt.title'),
            'sub_title' => $request->input('first_attempt.sub_title'),
            'publish_marksheet' => $request->boolean('first_attempt.publish_marksheet'),
        ];
        $config['second_attempt'] = [
            'title' => $request->input('second_attempt.title'),
            'sub_title' => $request->input('second_attempt.sub_title'),
            'publish_marksheet' => $request->boolean('second_attempt.publish_marksheet'),
        ];
        $config['third_attempt'] = [
            'title' => $request->input('third_attempt.title'),
            'sub_title' => $request->input('third_attempt.sub_title'),
            'publish_marksheet' => $request->boolean('third_attempt.publish_marksheet'),
        ];
        $config['fourth_attempt'] = [
            'title' => $request->input('fourth_attempt.title'),
            'sub_title' => $request->input('fourth_attempt.sub_title'),
            'publish_marksheet' => $request->boolean('fourth_attempt.publish_marksheet'),
        ];
        $config['fifth_attempt'] = [
            'title' => $request->input('fifth_attempt.title'),
            'sub_title' => $request->input('fifth_attempt.sub_title'),
            'publish_marksheet' => $request->boolean('fifth_attempt.publish_marksheet'),
        ];

        $exam->config = $config;
        $exam->save();
    }

    public function reorder(Request $request): void
    {
        $exams = $request->exams ?? [];

        $allExams = Exam::query()
            ->byPeriod()
            ->get();

        foreach ($exams as $index => $examItem) {
            $exam = $allExams->firstWhere('uuid', Arr::get($examItem, 'uuid'));

            if (! $exam) {
                continue;
            }

            $exam->position = $index + 1;
            $exam->save();
        }
    }
}
