<?php

namespace App\Imports\Exam;

use App\Concerns\ItemImport;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Subject;
use App\Models\Exam\Schedule;
use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MarkImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    private $additionalData = [];

    protected $limit = 1000;

    protected $trimExcept = [];

    public function __construct($additionalData)
    {
        $this->additionalData = $additionalData;
    }

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $examRows = $rows->filter(function ($item) {
            return ! empty($item['exam_code']) && ! empty($item['attempt']) && ! empty($item['course_name']) && ! empty($item['subject_code']);
        })->unique(function ($item) {
            return $item['exam_code'].'-'.strtolower($item['attempt']).'-'.$item['course_name'].'-'.$item['subject_code'];
        })->values()->map(function ($item) {
            return [
                'attempt' => strtolower($item['attempt']),
                'exam_code' => $item['exam_code'],
                'course_name' => $item['course_name'],
                'subject_code' => $item['subject_code'],
            ];
        });

        $courses = Course::query()
            ->with('batches')
            ->byPeriod()
            ->whereIn('name', $rows->pluck('course_name')->unique()->all())
            ->get();

        $params['courses'] = $courses;

        $courseIds = $courses->pluck('id')->all();
        $batchIds = $courses->pluck('batches')->flatten()->pluck('id')->all();

        $subjects = Subject::query()
            ->byPeriod()
            ->whereIn('code', $rows->pluck('subject_code')->unique()->all())
            ->get();

        $params['subjects'] = $subjects;

        $query = Schedule::query()
            ->select('exam_schedules.*')
            ->join('exams', 'exam_schedules.exam_id', '=', 'exams.id');

        foreach ($examRows as $examRow) {
            $query->orWhere(function ($q) use ($examRow, $courses) {
                $filteredCourses = $courses->filter(function ($course) use ($examRow) {
                    return $course->name == $examRow['course_name'];
                });

                $q->where('exams.code', $examRow['exam_code'])
                    ->where('exam_schedules.attempt', $examRow['attempt'])
                    ->where(function ($q) use ($filteredCourses) {
                        $q->whereIn('course_id', $filteredCourses->pluck('id')->all())
                            ->orWhereIn('batch_id', $filteredCourses->pluck('batches')->flatten()->pluck('id')->all());
                    });
            });
        }

        $scheduleIds = $query->get()->pluck('id')->all();

        $schedules = Schedule::query()
            ->with('records', 'assessment')
            ->whereIn('id', $scheduleIds)
            ->get();

        $params['schedules'] = $schedules;

        $students = Student::query()
            ->select('students.id', 'students.uuid', 'students.roll_number', 'students.batch_id', 'admissions.code_number')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->whereIn('students.batch_id', $batchIds)
            ->get();

        $params['students'] = $students;

        $assessments = [];
        foreach ($schedules as $schedule) {
            $scheduleAssessment = collect($schedule->assessment->records ?? []);

            foreach ($schedule->records as $examRecord) {
                if (! $examRecord->getConfig('has_exam')) {
                    continue;
                }

                $assessments[$schedule->batch_id][$examRecord->subject_id] = collect($examRecord->getConfig('assessments', []))
                    ->filter(function ($assessment) {
                        return Arr::get($assessment, 'max_mark', 0) > 0;
                    })
                    ->map(function ($assessment) use ($scheduleAssessment) {
                        $code = Arr::get($assessment, 'code');

                        return [
                            'name' => Arr::get($scheduleAssessment->firstWhere('code', $code), 'name'),
                            'code' => $code,
                            'max_mark' => Arr::get($assessment, 'max_mark'),
                        ];
                    })->toArray();
            }
        }

        $params['assessments'] = $assessments;

        $logFile = $this->getLogFile('exam-mark');

        $errors = $this->validate($rows, $params);

        $this->checkForErrors('exam-mark', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows, $params);
        }
    }

    private function import(Collection $rows, array $params = [])
    {
        activity()->disableLogging();

        $rows = $this->trimInput($rows);

        $students = Arr::get($params, 'students', collect([]));
        $assessments = Arr::get($params, 'assessments', []);

        $courses = Arr::get($params, 'courses', collect([]));
        $subjects = Arr::get($params, 'subjects', collect([]));

        $schedules = Arr::get($params, 'schedules', collect([]));
        $assessments = Arr::get($params, 'assessments', []);

        \DB::beginTransaction();

        $newMarks = [];
        foreach ($rows as $index => $row) {
            $admissionNumber = Arr::get($row, 'admission_number');
            $rollNumber = Arr::get($row, 'roll_number');
            $courseName = Arr::get($row, 'course_name');
            $subjectCode = Arr::get($row, 'subject_code');
            $attempt = strtolower(Arr::get($row, 'attempt'));

            $course = $courses->where('name', $courseName)->first();
            $subject = $subjects->where('code', $subjectCode)->first();

            if ($admissionNumber) {
                $student = $students->where('code_number', $admissionNumber)->first();
            } elseif ($rollNumber) {
                $student = $students->where('roll_number', $rollNumber)->first();
            }

            $schedule = $schedules->where('batch_id', $student->batch_id)
                ->where('attempt.value', $attempt)
                ->first();

            $examRecord = $schedule->records->firstWhere('subject_id', $subject->id);

            $recordMarks = collect($examRecord->marks ?? []);

            $scheduleAssessments = $assessments[$student->batch_id][$subject->id] ?? [];

            $assessmentMarks = [];
            foreach ($scheduleAssessments as $assessment) {
                $assessmentMarks[$assessment['code']] = $recordMarks->firstWhere('code', $assessment['code'])['marks'] ?? [];
            }

            foreach ($scheduleAssessments as $assessment) {
                $assessmentMark = collect($assessmentMarks[$assessment['code']]);

                if (Arr::has($row, Str::camel($assessment['code']))) {
                    $obtainedMark = Arr::get($row, Str::camel($assessment['code']));

                    if (in_array($obtainedMark, ['A', 'a', 'Ab', 'ab'])) {
                        $obtainedMark = 'A';
                    }

                    $studentMark = $assessmentMark->firstWhere('uuid', $student->uuid);

                    if ($studentMark) {
                        $assessmentMark = $assessmentMark->reject(function ($mark) use ($student) {
                            return $mark['uuid'] == $student->uuid;
                        });
                    }

                    $assessmentMark->push([
                        'uuid' => $student->uuid,
                        'obtained_mark' => $obtainedMark,
                    ]);

                    $assessmentMarks[$assessment['code']] = $assessmentMark->toArray();
                } else {
                    $assessmentMarks[$assessment['code']] = $assessmentMark->toArray();
                }
            }

            $newMarks = collect($assessmentMarks)->map(function ($marks, $code) {
                return [
                    'code' => $code,
                    'marks' => $marks,
                ];
            })->toArray();

            $examRecord->marks = $newMarks;
            $examRecord->save();
        }

        \DB::commit();

        activity()->enableLogging();
    }

    private function validate(Collection $rows, array $params = [])
    {
        $rows = $this->trimInput($rows);

        $students = Arr::get($params, 'students', collect([]));
        $assessments = Arr::get($params, 'assessments', []);

        $courses = Arr::get($params, 'courses', collect([]));
        $subjects = Arr::get($params, 'subjects', collect([]));

        $schedules = Arr::get($params, 'schedules', collect([]));
        $assessments = Arr::get($params, 'assessments', []);

        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $admissionNumber = Arr::get($row, 'admission_number');
            $rollNumber = Arr::get($row, 'roll_number');
            $courseName = Arr::get($row, 'course_name');
            $subjectCode = Arr::get($row, 'subject_code');

            $course = $courses->where('name', $courseName)->first();

            if (! $course) {
                $errors[] = $this->setError($rowNo, trans('academic.course.course'), 'invalid');
            }

            $subject = $subjects->where('code', $subjectCode)->first();

            if (! $subject) {
                $errors[] = $this->setError($rowNo, trans('academic.subject.subject'), 'invalid');
            }

            $student = null;

            if (! $admissionNumber && ! $rollNumber) {
                $errors[] = $this->setError($rowNo, trans('student.student'), 'invalid');
            } elseif ($admissionNumber) {
                $student = $students->where('code_number', $admissionNumber)->first();

                if (! $student) {
                    $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'invalid');
                }
            } else {
                $student = $students->where('roll_number', $rollNumber)->first();

                if (! $student) {
                    $errors[] = $this->setError($rowNo, trans('student.roll_number.roll_number'), 'invalid');
                }
            }

            if ($student) {
                $schedule = $schedules->where('batch_id', $student->batch_id)->first();

                if (! $schedule) {
                    $errors[] = $this->setError($rowNo, trans('exam.schedule.schedule'), 'invalid');
                } else {
                    $scheduleAssessments = $assessments[$student->batch_id][$subject->id] ?? [];

                    if (empty($scheduleAssessments)) {
                        $errors[] = $this->setError($rowNo, trans('academic.subject.subject'), 'invalid');
                    } else {

                        foreach ($scheduleAssessments as $assessment) {
                            if (Arr::has($row, Str::camel($assessment['code']))) {
                                $obtainedMark = Arr::get($row, Str::camel($assessment['code']));

                                if (in_array($obtainedMark, ['A', 'a', 'Ab', 'ab'])) {
                                    $obtainedMark = 'A';
                                } elseif (! is_numeric($obtainedMark)) {
                                    $errors[] = $this->setError($rowNo, $assessment['name'], 'numeric');
                                } elseif ($obtainedMark < 0 || $obtainedMark > $assessment['max_mark']) {
                                    $errors[] = $this->setError($rowNo, $assessment['name'], 'min_max', ['min' => 0, 'max' => $assessment['max_mark'], 'numeric' => true]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    private function trimInput(Collection $rows)
    {
        return collect($rows)
            ->map(function ($row) {
                return collect($row)->map(function ($value, $key) {
                    return in_array($key, $this->trimExcept) ? $value : trim($value);
                })->all();
            })->all();
    }
}
