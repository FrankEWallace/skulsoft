<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\Student\TimesheetResource;
use App\Models\Student\Student;
use App\Models\Student\Timesheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TimesheetListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date', 'in_at', 'out_at'];

    protected $defaultSort = 'in_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'student',
                'label' => trans('student.student'),
                'print_label' => 'student.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'student.course_name + student.batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('student.timesheet.props.date'),
                'print_label' => 'date.formatted',
                'print_sub_label' => 'day',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'inAt',
                'label' => trans('student.timesheet.props.in_at'),
                'print_label' => 'in_at_date.formatted',
                'print_sub_label' => 'in_at_time.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'outAt',
                'label' => trans('student.timesheet.props.out_at'),
                'print_label' => 'out_at_date.formatted',
                'print_sub_label' => 'out_at_time.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'duration',
                'label' => trans('student.timesheet.props.duration'),
                'print_label' => 'duration',
                'sortable' => false,
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
        $batches = Str::toArray($request->query('batches'));
        $students = Str::toArray($request->query('students'));

        $studentIds = [];
        $isStudentOrGuardian = auth()->user()->hasAnyRole(['student', 'guardian']);

        if ($isStudentOrGuardian) {
            $studentIds = Student::query()
                ->summary()
                ->filterAccessible()
                ->get()
                ->pluck('id')
                ->all();
        }

        return Timesheet::query()
            ->with(['student' => fn ($q) => $q->summary()])
            ->when($isStudentOrGuardian, function ($q) use ($studentIds) {
                $q->whereIn('student_id', $studentIds);
            })
            ->when($batches, function ($q) use ($batches) {
                $q->whereHas('student', function ($q) use ($batches) {
                    $q->whereHas('batch', function ($q) use ($batches) {
                        $q->whereIn('uuid', $batches);
                    });
                });
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return TimesheetResource::collection($this->filter($request)
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
