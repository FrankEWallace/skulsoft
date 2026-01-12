<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\EnrollmentSeatResource;
use App\Domain\Academic\Models\EnrollmentSeat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class EnrollmentSeatListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course.name',
                'print_sub_label' => 'course.term',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'enrollmentType',
                'label' => trans('student.enrollment_type.enrollment_type'),
                'print_label' => 'enrollment_type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'seat',
                'label' => trans('academic.enrollment_seat.props.seat'),
                'print_label' => 'seat',
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
        $courses = Str::toArray($request->query('courses'));
        $enrollmentTypes = Str::toArray($request->query('enrollment_types'));

        return EnrollmentSeat::query()
            ->with('course', 'enrollmentType')
            ->whereHas('course', function ($q) {
                $q->byPeriod()
                    ->filterAccessible();
            })
            ->when($courses, function ($q, $courses) {
                $q->whereHas('course', function ($q) use ($courses) {
                    $q->whereIn('uuid', $courses);
                });
            })
            ->when($enrollmentTypes, function ($q, $enrollmentTypes) {
                $q->whereHas('enrollmentType', function ($q) use ($enrollmentTypes) {
                    $q->whereIn('uuid', $enrollmentTypes);
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
        $query = $this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder());

        return EnrollmentSeatResource::collection($query
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
