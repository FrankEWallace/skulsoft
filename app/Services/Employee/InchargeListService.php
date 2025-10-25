<?php

namespace App\Services\Employee;

use App\Contracts\ListGenerator;
use App\Http\Resources\Employee\InchargeResource;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InchargeListService extends ListGenerator
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
                'key' => 'detail',
                'label' => trans('general.detail'),
                'print_label' => 'name',
                'print_sub_label' => 'type',
                'print_additional_label' => 'detail',
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
        return Incharge::query()
            ->with('model', 'detail')
            ->whereEmployeeId($employee->id)
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request, Employee $employee): AnonymousResourceCollection
    {
        return InchargeResource::collection($this->filter($request, $employee)
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
