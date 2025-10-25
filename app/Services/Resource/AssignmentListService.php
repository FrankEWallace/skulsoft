<?php

namespace App\Services\Resource;

use App\Contracts\ListGenerator;
use App\Http\Resources\Resource\AssignmentResource;
use App\Models\Resource\Assignment;
use App\Models\Student\Student;
use App\Support\HasFilterByAssignedSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class AssignmentListService extends ListGenerator
{
    use HasFilterByAssignedSubject;

    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'title',
                'label' => trans('resource.assignment.props.title'),
                'print_label' => 'title_excerpt',
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
                'key' => 'date',
                'label' => trans('resource.assignment.props.date'),
                'print_label' => 'date.formatted',
                'print_sub_label' => 'due_date.formatted',
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
        $types = Str::toArray($request->query('types'));

        $filterSubjectUuids = $this->getFilteredSubjects();

        return Assignment::query()
            ->with([
                'type',
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
            ->when(auth()->user()->hasRole('student'), function ($q) {
                $studentId = Student::query()
                    ->auth()
                    ->first()?->id;

                $q->withCount(['submissions' => function ($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                }]);
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
            ->when($types, function ($q, $types) {
                $q->whereHas('type', function ($q) use ($types) {
                    $q->whereIn('uuid', $types);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:search,title,description',
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
                'App\QueryFilters\DateBetween:start_due_date,end_due_date,due_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if (auth()->user()->is_student_or_guardian) {
            $view = $request->query('view', 'card');
            $request->merge(['view' => $view]);
        }

        return AssignmentResource::collection($this->filter($request)
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
