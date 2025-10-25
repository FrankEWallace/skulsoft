<?php

namespace App\Services\Exam;

use App\Contracts\ListGenerator;
use App\Http\Resources\Exam\FormResource;
use App\Models\Exam\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class FormListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'submitted_at', 'approved_at'];

    protected $defaultSort = 'submitted_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'exam',
                'label' => trans('exam.exam'),
                'print_label' => 'schedule.exam.name',
                'print_sub_label' => 'schedule.exam.term.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'student',
                'label' => trans('student.student'),
                'print_label' => 'student.name',
                'print_sub_label' => 'student.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'batch',
                'label' => trans('academic.batch.batch'),
                'print_label' => 'student.course_name',
                'print_sub_label' => 'student.batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'submittedAt',
                'label' => trans('exam.form.props.submitted_at'),
                'print_label' => 'submitted_at.value',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'approvedAt',
                'label' => trans('exam.form.props.approved_at'),
                'print_label' => 'approved_at.value',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $attempt = $request->query('attempt');
        $exams = Str::toArray($request->query('exams'));
        $batches = Str::toArray($request->query('batches'));

        return Form::query()
            ->with([
                'schedule.exam.term.division',
                'student' => fn ($q) => $q->summary(),
            ])
            ->filterAccessible()
            ->when($exams, function ($q, $exams) use ($attempt) {
                $q->whereHas('schedule', function ($q) use ($exams, $attempt) {
                    $q->whereHas('exam', function ($q) use ($exams) {
                        $q->whereIn('uuid', $exams);
                    })
                        ->when($attempt, function ($q) use ($attempt) {
                            $q->where('attempt', $attempt);
                        });
                });
            })
            ->when($batches, function ($q, $batches) {
                $q->whereHas('student', function ($q) use ($batches) {
                    $q->whereHas('batch', function ($q) use ($batches) {
                        $q->whereIn('uuid', $batches);
                    });
                });
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return FormResource::collection($this->filter($request)
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

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
