<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\Student\StudentSummaryResource;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class StudentSummaryListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('student.admission.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'joining_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('contact.props.name'),
                'print_label' => 'name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                // 'print_sub_label' => 'batch_name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'gender',
                'label' => trans('contact.props.gender'),
                'print_label' => 'gender.label',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'birthDate',
                'label' => trans('contact.props.birth_date'),
                'print_label' => 'birth_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'contactNumber',
                'label' => trans('contact.props.contact_number'),
                'print_label' => 'contact_number',
                'print_sub_label' => 'email',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'parent',
                'label' => trans('student.props.parent'),
                'print_label' => 'father_name',
                'print_sub_label' => 'mother_name',
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
        $batches = Str::toArray($request->query('batches'));
        $status = $request->query('status', 'studying');

        $date = today()->toDateString();

        return Student::query()
            ->summary()
            ->when($request->boolean('allPeriods', true), function ($q) {
                $q->byPeriod();
            })
            ->filterByStatus($status)
            ->when($batches, function ($q, $batches) {
                $q->whereIn('batches.uuid', $batches);
            })
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%")
                    ->orWhere('admissions.code_number', 'like', "%{$name}%");
            })
            ->filter([
                'App\QueryFilters\UuidMatch:students.uuid',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $query = $this->filter($request);

        $query->orderBy($this->getSort(), $this->getOrder());

        return StudentSummaryResource::collection($query
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
