<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\CourseInchargeResource;
use App\Models\Academic\Course;
use App\Models\Incharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CourseInchargeListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'start_date'];

    protected $defaultSort = 'start_date';

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
                'key' => 'employee',
                'label' => trans('employee.employee'),
                'print_label' => 'employee.name',
                'print_sub_label' => 'employee.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'period',
                'label' => trans('employee.incharge.props.period'),
                'print_label' => 'period',
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
        $courses = Str::toArray($request->query('courses'));
        $employees = Str::toArray($request->query('employees'));

        return Incharge::query()
            ->whereHasMorph(
                'model',
                [Course::class],
                function (Builder $query) use ($courses) {
                    $query->byPeriod()
                        ->when($courses, function ($q, $courses) {
                            $q->whereIn('uuid', $courses);
                        });
                }
            )
            ->with(['model', 'employee' => fn ($q) => $q->summary()])
            ->when($employees, function ($q, $employees) {
                $q->whereHas('employee', function ($q) use ($employees) {
                    $q->whereIn('uuid', $employees);
                });
            })
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return CourseInchargeResource::collection($this->filter($request)
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
