<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\Student\StudentResource;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecordListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'start_date'];

    protected $defaultSort = 'start_date';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'period',
                'label' => trans('academic.period.period'),
                'print_label' => 'period.name',
                'print_sub_label' => 'code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'enrollmentType',
                'label' => trans('student.enrollment_type.enrollment_type'),
                'print_label' => 'enrollment_type_name',
                'print_sub_label' => 'enrollment_status_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                // 'print_sub_label' => 'enrollment_type_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'startDate',
                'label' => trans('student.props.start_date'),
                'print_label' => 'start_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('student.props.end_date'),
                'print_label' => 'end_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, Student $student): Builder
    {
        return Student::query()
            ->select('students.*', 'admissions.number_format', 'admissions.number', 'admissions.code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'options.name as transfer_reason', 'enrollment_types.name as enrollment_type_name', 'enrollment_statuses.name as enrollment_status_name')
            ->join('batches', function ($join) {
                $join->on('students.batch_id', '=', 'batches.id')
                    ->leftJoin('courses', function ($join) {
                        $join->on('batches.course_id', '=', 'courses.id');
                    });
            })
            ->join('admissions', function ($join) {
                $join->on('students.admission_id', '=', 'admissions.id');
            })
            ->leftJoin('options', function ($join) {
                $join->on('admissions.transfer_reason_id', '=', 'options.id');
            })
            ->leftJoin('options as enrollment_types', function ($join) {
                $join->on('students.enrollment_type_id', '=', 'enrollment_types.id');
            })
            ->leftJoin('options as enrollment_statuses', function ($join) {
                $join->on('students.enrollment_status_id', '=', 'enrollment_statuses.id');
            })
            ->with('period', 'enrollmentType', 'enrollmentStatus')
            ->whereContactId($student->contact_id)
            ->whereNull('students.cancelled_at')
            ->filter([
                //
            ]);
    }

    public function paginate(Request $request, Student $student): AnonymousResourceCollection
    {
        return StudentResource::collection($this->filter($request, $student)
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

    public function list(Request $request, Student $student): AnonymousResourceCollection
    {
        return $this->paginate($request, $student);
    }
}
