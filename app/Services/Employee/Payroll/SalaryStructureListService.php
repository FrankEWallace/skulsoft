<?php

namespace App\Services\Employee\Payroll;

use App\Concerns\SubordinateAccess;
use App\Contracts\ListGenerator;
use App\Http\Resources\Employee\Payroll\SalaryStructureResource;
use App\Models\Employee\Payroll\SalaryStructure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class SalaryStructureListService extends ListGenerator
{
    use SubordinateAccess;

    protected $allowedSorts = ['created_at', 'effective_date', 'net_earning', 'net_deduction', 'net_salary'];

    protected $defaultSort = 'effective_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'employee',
                'label' => trans('employee.employee'),
                'print_label' => 'employee.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'designation',
                'label' => trans('employee.designation.designation'),
                'print_label' => 'employee.designation',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'template',
                'label' => trans('employee.payroll.salary_template.salary_template'),
                'print_label' => 'template.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'effectiveDate',
                'label' => trans('employee.payroll.salary_structure.props.effective_date'),
                'print_label' => 'effective_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'netEarning',
                'label' => trans('employee.payroll.salary_structure.props.net_earning'),
                'print_label' => 'net_earning.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'netDeduction',
                'label' => trans('employee.payroll.salary_structure.props.net_deduction'),
                'print_label' => 'net_deduction.formatted',
                'print_sub_label' => 'net_employee_contribution.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'netSalary',
                'label' => trans('employee.payroll.salary_structure.props.net_salary'),
                'print_label' => 'net_salary.formatted',
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
        $accessibleEmployeeIds = $this->getAccessibleEmployeeIds();

        $employees = Str::toArray($request->query('employees'));

        return SalaryStructure::query()
            ->with(['template', 'employee' => fn ($q) => $q->summary()])
            ->whereHas('employee', function ($q) use ($accessibleEmployeeIds, $employees) {
                $q->whereIn('id', $accessibleEmployeeIds)->when($employees, function ($q) use ($employees) {
                    $q->whereIn('uuid', $employees);
                });
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,effective_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return SalaryStructureResource::collection($this->filter($request)
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
