<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\CourseResource;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CourseListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('academic.course.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'term',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'division',
                'label' => trans('academic.division.division'),
                'print_label' => 'division.name',
                'print_sub_label' => 'division.program.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'code',
                'label' => trans('academic.course.props.code'),
                'print_label' => 'code',
                'print_sub_label' => 'shortcode',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'incharge',
                'label' => trans('academic.course_incharge.course_incharge'),
                'print_label' => 'incharges',
                'print_key' => 'employee.name',
                'print_sub_key' => 'period',
                'type' => 'array',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'batches',
                'label' => trans('academic.batch.batches'),
                'print_label' => 'batches',
                'print_key' => 'name',
                'type' => 'array',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'subjects',
                'label' => trans('academic.subject.subjects'),
                'print_label' => 'subject_records',
                'print_key' => 'subject.name',
                'type' => 'array',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'enableRegistration',
                'label' => trans('academic.course.registration'),
                'print_label' => 'enable_registration',
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
        $details = $request->query('details');
        $divisions = Str::toArray($request->query('divisions'));

        $periodId = null;

        if ($request->query('period')) {
            $periodId = Period::query()
                ->whereUuid($request->query('period'))
                ->first()?->id;
        }

        return Course::query()
            ->with('division.program', 'batches', 'subjectRecords.subject')
            ->byPeriod($periodId)
            ->filterAccessible()
            ->when($details, function ($q) {
                $q->withCurrentIncharges();
            })
            ->when($divisions, function ($q, $divisions) {
                $q->whereHas('division', function ($q) use ($divisions) {
                    $q->whereIn('uuid', $divisions);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:code',
                'App\QueryFilters\LikeMatch:shortcode',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return CourseResource::collection($this->filter($request)
                ->orderBy('position', 'asc')
                ->get());
        }

        $query = $this->filter($request);

        if (! $request->query('sort')) {
            $query->orderBy('courses.position', 'asc');
        } else {
            $query->orderBy($this->getSort(), $this->getOrder());
        }

        return CourseResource::collection($query
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
