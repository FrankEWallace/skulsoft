<?php

namespace App\Concerns\Exam;

use Illuminate\Support\Str;

trait MarksheetLayout
{
    public function getMarksheetTypes()
    {
        $types = [];

        if (config('config.exam.marksheet_format') == 'India') {
            $types = [
                ['label' => trans('exam.marksheet.exam_wise_credit_based'), 'value' => 'exam_wise_credit_based', 'requires_exam' => true],
                ['label' => trans('exam.marksheet.exam_wise'), 'value' => 'exam_wise', 'requires_exam' => true],
                ['label' => trans('exam.marksheet.term_wise'), 'value' => 'term_wise', 'requires_term' => true],
                ['label' => trans('exam.marksheet.cumulative'), 'value' => 'cumulative'],
            ];
        } elseif (config('config.exam.marksheet_format') == 'Cameroon') {
            $types = [
                ['label' => trans('exam.marksheet.exam_wise'), 'value' => 'exam_wise_cameroon', 'requires_exam' => true],
                ['label' => trans('exam.marksheet.term_wise'), 'value' => 'term_wise_cameroon', 'requires_term' => true],
            ];
        } elseif (config('config.exam.marksheet_format') == 'Ghana') {
            $types = [
                ['label' => trans('exam.marksheet.exam_wise'), 'value' => 'exam_wise_ghana', 'requires_exam' => true],
            ];
        }

        return $types;
    }

    public function getTemplates()
    {
        $predefinedTemplates = collect(glob(resource_path('views/print/exam/marksheet/*.blade.php')))
            ->filter(function ($template) {
                return ! in_array(basename($template), ['header.blade.php', 'sub-header.blade.php']);
            })
            ->map(function ($template) {
                return basename($template, '.blade.php');
            });

        $customTemplates = collect(glob(resource_path('views/print/custom/exam/marksheet/*.blade.php')))
            ->filter(function ($template) {
                return ! in_array(basename($template), ['header.blade.php', 'sub-header.blade.php']);
            })
            ->map(function ($template) {
                return basename($template, '.blade.php');
            });

        $templates = collect($predefinedTemplates->merge($customTemplates))
            ->unique()
            ->filter(function ($template) {
                if (config('config.exam.marksheet_format') == 'India') {
                    return ! Str::contains($template, ['cameroon', 'ghana']);
                } elseif (config('config.exam.marksheet_format') == 'Cameroon') {
                    return Str::endsWith($template, '-cameroon');
                } elseif (config('config.exam.marksheet_format') == 'Ghana') {
                    return Str::endsWith($template, '-ghana');
                }

                return true;
            })
            ->map(function ($template) {
                return [
                    'label' => Str::toWord($template),
                    'value' => $template,
                ];
            })
            ->values();

        return $templates;
    }
}
