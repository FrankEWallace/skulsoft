<?php

namespace App\Services\Exam;

use App\Contracts\ListGenerator;
use App\Http\Resources\Exam\OnlineExamSubmissionResource;
use App\Models\Exam\OnlineExam;
use App\Models\Exam\OnlineExamSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OnlineExamSubmissionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'studentName',
                'label' => trans('student.props.name'),
                'print_label' => 'student_name',
                'print_sub_label' => 'admission_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'courseName',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'submittedAt',
                'label' => trans('exam.online_exam.submission.props.submitted_at'),
                'print_label' => 'submitted_at.formatted',
                'print_sub_label' => 'started_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'evaluatedAt',
                'label' => trans('exam.online_exam.submission.props.evaluated_at'),
                'print_label' => 'evaluated_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'obtainedMark',
                'label' => trans('exam.online_exam.submission.props.obtained_mark'),
                'print_label' => 'obtained_mark',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, OnlineExam $onlineExam): Builder
    {
        return OnlineExamSubmission::query()
            ->select('online_exam_submissions.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as student_name'), 'batches.name as batch_name', 'courses.name as course_name', 'admissions.code_number as admission_number')
            ->whereOnlineExamId($onlineExam->id)
            ->join('students', 'students.id', '=', 'online_exam_submissions.student_id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->join('contacts', 'contacts.id', '=', 'students.contact_id')
            ->join('batches', 'batches.id', '=', 'students.batch_id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->filter([
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request, OnlineExam $onlineExam): AnonymousResourceCollection
    {
        return OnlineExamSubmissionResource::collection($this->filter($request, $onlineExam)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request, OnlineExam $onlineExam): AnonymousResourceCollection
    {
        return $this->paginate($request, $onlineExam);
    }
}
