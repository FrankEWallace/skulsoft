<?php

namespace App\Services\Resource\Report;

use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\SubjectRecord;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Resource\Diary;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DateWiseStudentDiaryService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function generate(Request $request)
    {
        $filteredBatches = Str::toArray($request->query('batches'));
        $status = $request->query('status', 'all');

        $batches = Batch::query()
            ->with('course')
            ->byPeriod()
            ->filterAccessible()
            ->when($filteredBatches, function ($q) use ($filteredBatches) {
                $q->whereIn('uuid', $filteredBatches);
            })
            ->get();

        $subjects = Subject::query()
            ->byPeriod()
            ->get();

        $subjectRecords = SubjectRecord::query()
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->where('has_grading', false)
            ->get();

        $subjectIncharges = Incharge::query()
            ->whereModelType('Subject')
            ->whereIn('model_id', $subjects->pluck('id'))
            ->whereDetailType('Batch')
            ->whereIn('detail_id', $batches->pluck('id'))
            ->get();

        $employees = Employee::query()
            ->with('contact')
            ->get();

        $diaries = Diary::query()
            ->byPeriod()
            ->with('records')
            ->where('date', $request->query('date', today()->toDateString()))
            ->get();

        $data = [];
        foreach ($batches as $batch) {
            $batchSubjectRecords = $subjectRecords->filter(function ($subjectRecord) use ($batch) {
                return $subjectRecord->batch_id == $batch->id || $subjectRecord->course_id == $batch->course_id;
            });

            $batchSubjects = [];
            foreach ($batchSubjectRecords as $subjectRecord) {
                $subject = $subjects->firstWhere('id', $subjectRecord->subject_id);

                if (! $subject) {
                    continue;
                }

                $incharge = $subjectIncharges->filter(function ($incharge) use ($subject, $batch) {
                    return $incharge->model_id === $subject->id && $incharge->detail_id === $batch->id;
                })->first();

                $inchargeEmployeeDetail = null;
                if ($incharge) {
                    $inchargeEmployee = $employees->firstWhere('id', Arr::get($incharge, 'employee_id'));
                    $inchargeEmployeeDetail = $inchargeEmployee?->contact?->name;
                }

                $subjectDiaries = $diaries->filter(function ($diary) use ($subject, $batch) {
                    return $diary->records->contains(function ($record) use ($subject, $batch) {
                        return $record->subject_id === $subject->id && $record->batch_id === $batch->id;
                    });
                });

                $submittedEmployees = [];
                foreach ($subjectDiaries as $diary) {
                    $submittedEmployees[] = [
                        'name' => $employees->firstWhere('id', $diary->employee_id)?->contact?->name,
                        'created_at' => \Cal::dateTime($diary->created_at)?->formatted,
                    ];
                }

                if ($status == 'all' || ($status == 'submitted' && count($submittedEmployees) > 0) || ($status == 'not_submitted' && count($submittedEmployees) == 0)) {
                    $batchSubjects[] = [
                        'name' => $subject?->name,
                        'code' => $subject?->code,
                        'employees' => $submittedEmployees,
                        'incharge' => $inchargeEmployeeDetail,
                    ];
                }
            }

            if (count($batchSubjects) > 0) {
                $data[] = [
                    'batch' => $batch->course->name.' - '.$batch->name,
                    'subjects' => $batchSubjects,
                ];
            }
        }

        $date = \Cal::date($request->query('date', today()->toDateString()))?->formatted;

        $layout = [
            'watermark' => true,
            'show_print_date_time' => true,
        ];

        if ($request->query('output') == 'pdf') {
            $content = view()->first([config('config.print.custom_path').'resource.report.date-wise-student-diary', 'print.resource.report.date-wise-student-diary'], compact('data', 'layout', 'date'))->render();

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
            ]);
            // to support unicode characters
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            $mpdf->WriteHTML($content);
            $mpdf->Output();

            return;
        }

        return view()->first([config('config.print.custom_path').'resource.report.date-wise-student-diary', 'print.resource.report.date-wise-student-diary'], compact('data', 'layout', 'date'))->render();
    }
}
