<?php

namespace App\Services\Resource;

use App\Contracts\ListGenerator;
use App\Http\Resources\Resource\OnlineClassResource;
use App\Models\Resource\OnlineClass;
use App\Support\HasFilterByAssignedSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class OnlineClassListService extends ListGenerator
{
    use HasFilterByAssignedSubject;

    protected $allowedSorts = ['created_at', 'start_at'];

    protected $defaultSort = 'start_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'topic',
                'label' => trans('resource.online_class.props.topic'),
                'print_label' => 'topic_excerpt',
                'sortable' => false,
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
                'key' => 'employee',
                'label' => trans('employee.employee'),
                'print_label' => 'employee.name',
                'print_sub_label' => 'employee.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'startAt',
                'label' => trans('resource.online_class.props.start_at'),
                'print_label' => 'start_at.formatted',
                'print_sub_label' => 'platform',
                'sortable' => true,
                'visibility' => true,
            ],
            // [
            //     'key' => 'createdAt',
            //     'label' => trans('general.created_at'),
            //     'print_label' => 'created_at.formatted',
            //     'sortable' => true,
            //     'visibility' => true,
            // ],
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

        return OnlineClass::query()
            ->byPeriod()
            ->withUserId()
            ->filterAccessible()
            ->with([
                'records.subject',
                'records.batch.course',
                'employee' => fn ($q) => $q->summary(),
            ])
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
                'App\QueryFilters\DateBetween:start_date,end_date,start_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if (auth()->user()->is_student_or_guardian) {
            $view = $request->query('view', 'card');
            $request->merge(['view' => $view]);
        }

        return OnlineClassResource::collection($this->filter($request)
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
