<?php

namespace App\Services\Exam;

use App\Contracts\ListGenerator;
use App\Http\Resources\Exam\OnlineExamResource;
use App\Models\Exam\OnlineExam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OnlineExamListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'title', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'title',
                'label' => trans('exam.online_exam.props.title'),
                'print_label' => 'title',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'records',
                'label' => trans('academic.batch.batch'),
                'type' => 'array',
                'print_key' => 'batch.course.name,batch.name',
                'print_sub_key' => 'subject.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('exam.online_exam.props.date'),
                'print_label' => 'date.formatted',
                'print_sub_label' => 'endDate.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'time',
                'label' => trans('exam.online_exam.props.time'),
                'print_label' => 'period',
                'print_sub_label' => 'duration',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'employee',
                'label' => trans('employee.employee'),
                'print_label' => 'employee.name',
                'print_sub_label' => 'employee.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'createdAt',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at.formatted',
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
        return OnlineExam::query()
            ->byPeriod()
            ->with([
                'records.subject',
                'records.batch.course',
                'employee' => fn ($q) => $q->summary(),
            ])
            ->withUserId()
            ->filterAccessible()
            ->when(auth()->user()->hasAnyRole(['student', 'guardian']), function ($query) {
                $query->whereNotNull('published_at');
            })
            ->filter([
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return OnlineExamResource::collection($this->filter($request)
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
