<?php

namespace App\Services\Resource;

use App\Contracts\ListGenerator;
use App\Http\Resources\Resource\LessonPlanResource;
use App\Models\Resource\LessonPlan;
use App\Support\HasFilterByAssignedSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class LessonPlanListService extends ListGenerator
{
    use HasFilterByAssignedSubject;

    protected $allowedSorts = ['created_at', 'start_date', 'end_date'];

    protected $defaultSort = 'start_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'topic',
                'label' => trans('resource.lesson_plan.props.topic'),
                'print_label' => 'topic_excerpt',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'records',
                'label' => trans('academic.batch.batch'),
                'print_label' => 'batch.course.name',
                'print_sub_label' => 'batch.name',
                'sortable' => false,
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
                'key' => 'startDate',
                'label' => trans('resource.lesson_plan.props.start_date'),
                'print_label' => 'start_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('resource.lesson_plan.props.end_date'),
                'print_label' => 'end_date.formatted',
                'sortable' => true,
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
        $employees = Str::toArray($request->query('employees'));
        $subjects = Str::toArray($request->query('subjects'));
        $batches = Str::toArray($request->query('batches'));

        $filterSubjectUuids = $this->getFilteredSubjects();

        return LessonPlan::query()
            ->with([
                'records.subject',
                'records.batch.course',
                'employee' => fn ($q) => $q->summary(),
            ])
            ->byPeriod()
            ->withUserId()
            ->filterAccessible()
            ->when($employees, function ($q, $employees) {
                $q->whereHas('employee', function ($q) use ($employees) {
                    $q->whereIn('uuid', $employees);
                });
            })
            ->when($subjects, function ($q, $subjects) {
                $q->whereHas('records', function ($q) use ($subjects) {
                    $q->whereHas('subject', function ($q) use ($subjects) {
                        $q->whereIn('uuid', $subjects);
                    });
                });
            })
            ->when($filterSubjectUuids, function ($q, $filterSubjectUuids) {
                $q->whereHas('records', function ($q) use ($filterSubjectUuids) {
                    $q->whereHas('subject', function ($q) use ($filterSubjectUuids) {
                        $q->whereIn('uuid', $filterSubjectUuids);
                    });
                });
            })
            ->when($batches, function ($q, $batches) {
                $q->whereHas('records', function ($q) use ($batches) {
                    $q->whereHas('batch', function ($q) use ($batches) {
                        $q->whereIn('uuid', $batches);
                    });
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:topic',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return LessonPlanResource::collection($this->filter($request)
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
