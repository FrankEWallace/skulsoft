<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\SubjectRecordResource;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubjectRecordListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'subject_records.created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course.name_with_term',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'batch',
                'label' => trans('academic.batch.batch'),
                'print_label' => 'batch.course.name',
                'print_sub_label' => 'batch.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'credit',
                'label' => trans('academic.subject.props.credit'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'maxClassPerWeek',
                'label' => trans('academic.subject.props.max_class_per_week'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'isElective',
                'label' => trans('academic.subject.props.is_elective'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'hasNoExam',
                'label' => trans('academic.subject.props.has_no_exam'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'hasGrading',
                'label' => trans('academic.subject.props.has_grading'),
                'sortable' => false,
                'visibility' => true,
            ],
            // [
            //     'key' => 'incharge',
            //     'label' => trans('academic.subject_incharge.subject_incharge'),
            //     'print_label' => 'incharges',
            //     'print_key' => 'employee.name',
            //     'type' => 'array',
            //     'sortable' => false,
            //     'visibility' => true,
            // ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, Subject $subject): Builder
    {
        $details = $request->query('details');
        $courseBatchSubject = $request->query('course_batch_subject');
        $courseBatch = $request->query('course_batch');
        $course = $request->query('course');

        return SubjectRecord::query()
            ->with('batch.course', 'course')
            ->whereSubjectId($subject->id)
            ->whereHas('subject', function ($q) {
                $q->byPeriod();
            })
            // ->when($details, function($q) {
            //     $q->withCurrentIncharges();
            // })
            ->when($courseBatchSubject, function ($q, $courseBatchSubject) {
                $q->with('subject')->where(function ($q) use ($courseBatchSubject) {
                    $q->whereHas('batch', function ($q) use ($courseBatchSubject) {
                        $q->where('name', 'like', "%{$courseBatchSubject}%")
                            ->orWhereHas('course', function ($q) use ($courseBatchSubject) {
                                $q->where('name', 'like', "%{$courseBatchSubject}%");
                            });
                    })->orWhereHas('subject', function ($q) use ($courseBatchSubject) {
                        $q->where('name', 'like', "%{$courseBatchSubject}%");
                    });
                });
            })
            ->when($courseBatch, function ($q, $courseBatch) {
                $q->where(function ($q) use ($courseBatch) {
                    $q->whereHas('batch', function ($q) use ($courseBatch) {
                        $q->where('name', 'like', "%{$courseBatch}%")
                            ->orWhereHas('course', function ($q) use ($courseBatch) {
                                $q->where('name', 'like', "%{$courseBatch}%");
                            });
                    });
                });
            })
            ->when($course, function ($q, $course) {
                $q->whereHas('batch', function ($q) use ($course) {
                    $q->whereHas('course', function ($q) use ($course) {
                        $q->where('name', 'like', "%{$course}%");
                    });
                });
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request, Subject $subject): AnonymousResourceCollection
    {
        return SubjectRecordResource::collection($this->filter($request, $subject)
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

    public function list(Request $request, Subject $subject): AnonymousResourceCollection
    {
        return $this->paginate($request, $subject);
    }
}
