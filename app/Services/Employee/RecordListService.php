<?php

namespace App\Services\Employee;

use App\Contracts\ListGenerator;
use App\Http\Resources\Employee\RecordResource;
use App\Models\Employee\Employee;
use App\Models\Employee\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecordListService extends ListGenerator
{
    protected $allowedSorts = ['start_date'];

    protected $defaultSort = 'start_date';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'startDate',
                'label' => trans('employee.record.props.period'),
                'print_label' => 'period',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'department',
                'label' => trans('employee.department.department'),
                'print_label' => 'department.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'designation',
                'label' => trans('employee.designation.designation'),
                'print_label' => 'designation.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'employmentStatus',
                'label' => trans('employee.employment_status.employment_status'),
                'print_label' => 'employment_status.name',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, Employee $employee): Builder
    {
        return Record::query()
            ->withDetail()
            ->whereEmployeeId($employee->id)
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request, Employee $employee): AnonymousResourceCollection
    {
        return RecordResource::collection($this->filter($request, $employee)
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

    public function list(Request $request, Employee $employee): AnonymousResourceCollection
    {
        return $this->paginate($request, $employee);
    }
}
