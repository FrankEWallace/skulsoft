<?php

namespace App\Services\Employee\Payroll;

use App\Actions\Employee\FetchAllEmployee;
use App\Contracts\ListGenerator;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Helpers\CalHelper;
use App\Http\Resources\Employee\Payroll\PayrollResource;
use App\Models\Employee\Payroll\Payroll;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayrollListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'start_date', 'end_date', 'total', 'paid'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('employee.payroll.props.code_number'),
                'sortable' => true,
                'visibility' => true,
            ],
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
                'key' => 'startDate',
                'label' => trans('employee.payroll.props.start_date'),
                'print_label' => 'start_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('employee.payroll.props.end_date'),
                'print_label' => 'end_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'total',
                'label' => trans('employee.payroll.props.total'),
                'print_label' => 'total.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            // array(
            //     'key' => 'paid',
            //     'label' => trans('employee.payroll.props.paid'),
            //     'print_label' => 'paid.formatted',
            //     'sortable' => true,
            //     'visibility' => true
            // ),
            // array(
            //     'key' => 'status',
            //     'label' => trans('employee.payroll.props.status'),
            //     'print_label' => 'status.label',
            //     'sortable' => true,
            //     'visibility' => true
            // ),
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
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $request->merge([
            'paginate' => false,
        ]);

        $status = $request->query('status');
        $batchUuid = $request->query('batch_uuid');

        if (! $request->has('status')) {
            $status = PayrollStatus::PROCESSED->value;
        }

        if ($request->has('batch_uuid') && ! $request->has('status')) {
            $status = null;
        }

        $salaryStartDate = $request->query('salary_start_date');
        $salaryEndDate = $request->query('salary_end_date');

        $salaryPeriod = false;
        if ($salaryStartDate && $salaryEndDate && CalHelper::validateDate($salaryStartDate) && CalHelper::validateDate($salaryEndDate) && $salaryStartDate <= $salaryEndDate) {
            $salaryPeriod = true;
        }

        // Forcefully do it to fetch employees after some filters
        $filterCodeNumber = $request->query('code_number');

        $request->merge([
            'type' => 'all',
            'status' => 'all',
            'code_number' => '',
        ]);

        $employees = (new FetchAllEmployee)->execute($request);

        $request->merge([
            'code_number' => $filterCodeNumber,
        ]);

        return Payroll::query()
            ->with(['employee' => fn ($q) => $q->summary()])
            ->whereIn('employee_id', $employees->pluck('id')->all())
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($batchUuid, function ($q) use ($batchUuid) {
                $q->where('meta->batch_uuid', $batchUuid);
            })
            ->when($salaryPeriod, function ($q) use ($salaryStartDate, $salaryEndDate) {
                $q->where('start_date', '=', $salaryStartDate)
                    ->where('end_date', '=', $salaryEndDate);
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\ExactMatch:code_number',
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return PayrollResource::collection($this->filter($request)
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
