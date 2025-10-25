<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\Student\LeaveRequestResource;
use App\Models\Student\LeaveRequest;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class LeaveRequestListService extends ListGenerator
{
    protected $allowedSorts = ['start_date', 'created_at'];

    protected $defaultSort = 'start_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('contact.props.name'),
                'print_label' => 'student.name',
                'print_sub_label' => 'student.contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'parent',
                'label' => trans('student.props.parent'),
                'print_label' => 'student.father_name',
                'print_sub_label' => 'student.mother_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'admissionDate',
                'label' => trans('student.admission.props.date'),
                'print_label' => 'student.joining_date.formatted',
                'print_sub_label' => 'student.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'student.course_name',
                'print_sub_label' => 'student.batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'period',
                'label' => trans('general.period'),
                'print_label' => 'start_date.formatted',
                'print_sub_label' => 'end_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'category',
                'label' => trans('student.leave_request.props.category'),
                'print_label' => 'category.name',
                'sortable' => false,
                'visibility' => true,
            ],
            // [
            //     'key' => 'status',
            //     'label' => trans('student.leave_request.props.status'),
            //     'print_label' => 'status.label',
            //     'sortable' => false,
            //     'visibility' => true,
            // ],
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
        $students = Str::toArray($request->query('students'));

        return LeaveRequest::query()
            ->with(['model' => fn ($q) => $q->summary(), 'category'])
            ->filterAccessible()
            ->when($students, function ($q, $students) {
                $q->whereHasMorph(
                    'model', [Student::class],
                    function ($q) use ($students) {
                        $q->whereIn('uuid', $students);
                    }
                );
            })
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return LeaveRequestResource::collection($this->filter($request)
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
