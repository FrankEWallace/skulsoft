<?php

namespace App\Services\Exam;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Enums\Exam\AssessmentAttempt;
use App\Http\Resources\Exam\ExamResource;
use App\Models\Academic\Batch;
use App\Models\Academic\Period;
use App\Models\Academic\SubjectRecord;
use App\Models\Exam\Exam;
use App\Models\Exam\Result;
use App\Models\Exam\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class AdmitCardService
{
    public function preRequisite(Request $request)
    {
        $exams = ExamResource::collection(Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->get());

        $attempts = AssessmentAttempt::getOptions();

        return compact('exams', 'attempts');
    }

    public function fetchReport(Request $request)
    {
        $request->validate([
            'exam' => 'required|uuid',
            'attempt' => ['required', new Enum(AssessmentAttempt::class)],
            'batch' => 'required|uuid',
            'title' => 'string|nullable|max:255',
            'column' => 'required|integer|min:1|max:2',
            'signatory_1' => 'string|nullable|max:255',
            'signatory_2' => 'string|nullable|max:255',
            'signatory_3' => 'string|nullable|max:255',
            'signatory_4' => 'string|nullable|max:255',
        ]);

        $exam = Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->whereUuid($request->exam)
            ->getOrFail(trans('exam.exam'), 'exam');

        $batch = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');

        $schedule = Schedule::query()
            ->with(['records.subject'])
            ->whereExamId($exam->id)
            ->whereBatchId($batch->id)
            ->whereAttempt($request->attempt)
            ->getOrFail(trans('exam.schedule.schedule'));

        if ($schedule->has_form && auth()->user()->hasAnyRole(['student', 'guardian'])) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        $subjectRecords = SubjectRecord::query()
            ->where(function ($q) use ($batch) {
                $q->where('course_id', $batch->course_id)
                    ->orWhere('batch_id', $batch->id);
            })
            ->whereIn('subject_id', $schedule->records->pluck('subject_id'))
            ->get();

        $subjects = [];

        // Preparing subjects
        foreach ($schedule->records as $examRecord) {
            $hasExam = $examRecord->getConfig('has_exam');

            if (! $hasExam && $examRecord->subject_id) {
                continue;
            }

            $name = $examRecord->subject_id ? $examRecord->subject->name : $examRecord->getConfig('subject_name');
            $code = $examRecord->subject_id ? $examRecord->subject->shortcode : $examRecord->getConfig('subject_code');

            $subjectRecord = $subjectRecords->firstWhere('subject_id', $examRecord->subject_id);

            $subjects[] = [
                'id' => $examRecord->subject_id,
                'name' => $name,
                'shortcode' => $code,
                'has_grading' => (bool) $subjectRecord?->has_grading,
                'date' => $examRecord->date->formatted,
                'start_time' => $examRecord->start_time?->formatted,
                'end_time' => $examRecord->end_time?->formatted,
            ];
        }

        $queryStudents = Str::toArray($request->query('students'));

        $params = $request->all();

        if (count($queryStudents)) {
            $params['students'] = $queryStudents;
            $params['select_all'] = false;
        } else {
            $params['select_all'] = true;
        }

        $students = (new FetchBatchWiseStudent)->execute($params);

        $filteredStudents = [];
        if ($request->attempt != AssessmentAttempt::FIRST->value) {
            $attemptNumber = AssessmentAttempt::getAttemptNumber($request->attempt);

            $previousAttempt = $attemptNumber - 1;
            $previousAttemptName = AssessmentAttempt::getAttempt($previousAttempt);

            $previousSchedule = Schedule::query()
                ->whereExamId($exam->id)
                ->whereBatchId($batch->id)
                ->whereAttempt($previousAttemptName)
                ->firstOrFail();

            $examResults = Result::query()
                ->whereExamId($exam->id)
                ->whereAttempt($previousAttemptName)
                ->whereIn('student_id', $students->pluck('id')->all())
                ->get();

            foreach ($students as $student) {
                $studentExamResult = $examResults->firstWhere('student_id', $student->id);

                if (! $studentExamResult) {
                    continue;
                }

                $reassessmentSubjects = Arr::get($studentExamResult->subjects, 'reassessment', []);

                if ($reassessmentSubjects) {
                    $filteredStudents[] = [
                        'uuid' => $student->uuid,
                        'subjects' => $reassessmentSubjects,
                    ];
                }
            }

            $students = $students->filter(function ($student) use ($filteredStudents) {
                return in_array($student->uuid, Arr::pluck($filteredStudents, 'uuid'));
            });
        }

        $students = $students->map(function ($student) use ($filteredStudents) {
            $filteredStudent = Arr::first($filteredStudents, function ($filteredStudent) use ($student) {
                return $filteredStudent['uuid'] == $student->uuid;
            });

            $student->subjects = $filteredStudent['subjects'] ?? null;

            return $student;
        });

        $period = Period::find(auth()->user()->current_period_id);

        $titles = [
            [
                'label' => $request->query('title', trans('exam.admit_card.admit_card')),
                'align' => 'center',
                'class' => 'heading',
            ],
            [
                'label' => $exam->name.' '.$period->code,
                'align' => 'center',
                'class' => 'mt-2 sub-heading',
            ],
        ];

        $boxWidth = match ((int) $request->query('column')) {
            1 => '100%',
            2 => '48%',
            3 => '33%',
            default => '100%',
        };

        $layout = [
            'column' => $request->query('column', 1),
            'box_width' => $boxWidth,
            'show_sno' => $request->boolean('show_sno', false),
            'show_print_date_time' => $request->boolean('show_print_date_time', false),
            'signatory1' => $request->query('signatory_1'),
            'signatory2' => $request->query('signatory_2'),
            'signatory3' => $request->query('signatory_3'),
            'signatory4' => $request->query('signatory_4'),
            'watermark' => $request->boolean('show_watermark', false),
        ];

        return view()->first([config('config.print.custom_path').'exam.admit-card.default', 'print.exam.admit-card.default'], compact('schedule', 'titles', 'students', 'subjects', 'period', 'layout'))->render();
    }
}
