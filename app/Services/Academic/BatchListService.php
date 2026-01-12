<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\BatchResource;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BatchListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('academic.batch.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course.name_with_term',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'maxStrength',
                'label' => trans('academic.batch.props.max_strength'),
                'print_label' => 'max_strength',
                'print_sub_label' => 'current_strength',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'incharge',
                'label' => trans('academic.batch_incharge.batch_incharge'),
                'print_label' => 'incharges',
                'print_key' => 'employee.name',
                'print_sub_key' => 'period',
                'type' => 'array',
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
        $withSubjects = $request->query('with_subjects');
        $courseBatch = $request->query('course_batch');
        $course = $request->query('course');
        $courses = Str::toArray($request->query('courses'));

        $periodId = null;

        if ($request->query('period')) {
            $periodId = Period::query()
                ->whereUuid($request->query('period'))
                ->first()?->id;
        }

        return Batch::query()
            ->select('batches.*', 'courses.position as course_position')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->when($courseBatch, function ($q, $courseBatch) {
                $q->select('batches.*', 'courses.position as course_position');
            })
            ->with('course')
            ->byPeriod($periodId)
            ->filterAccessible()
            ->when($details, function ($q) {
                $q->withCurrentIncharges()
                    ->withCount(['students as current_strength' => function ($query) {
                        $query->leftJoin('admissions', 'admissions.id', '=', 'students.admission_id')
                            ->where(function ($q) {
                                $q->whereNull('admissions.leaving_date')
                                    ->orWhere('admissions.leaving_date', '>', today()->toDateString());
                            });
                    }]);
            })
            ->when($withSubjects, function ($q) {
                $q->with('subjectRecords.subject');
            })
            ->when($courseBatch, function ($q, $courseBatch) {
                $q->where(\DB::raw('CONCAT(courses.name, " ", batches.name)'), 'like', "%{$courseBatch}%");
                // $q->where(function ($q) use ($courseBatch) {
                //     $q->where('name', 'like', "%{$courseBatch}%")
                //         ->orWhereHas('course', function ($q) use ($courseBatch) {
                //             $q->where('name', 'like', "%{$courseBatch}%");
                //         });
                // });
            })
            ->when($course, function ($q, $course) {
                $q->whereHas('course', function ($q1) use ($course) {
                    $q1->where('courses.uuid', $course);
                });
            })
            ->when($courses, function ($q, $courses) {
                $q->whereHas('course', function ($q) use ($courses) {
                    $q->whereIn('courses.uuid', $courses);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name,batches.name',
                'App\QueryFilters\UuidMatch:batches.uuid',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $query = $this->filter($request);

        if ($request->query('all')) {
            return BatchResource::collection($this->filter($request)
                ->orderBy('batches.position', 'asc')
                ->get());
        }

        if (! $request->query('sort') || $request->query('course_batch')) {
            $query->orderBy('courses.position', 'asc')
                ->orderBy('batches.position', 'asc');
        } elseif ($this->getSort() == 'name') {
            $query->orderBy('batches.name', $this->getOrder());
        }

        return BatchResource::collection($query
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
