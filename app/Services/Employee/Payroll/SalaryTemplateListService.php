<?php

namespace App\Services\Employee\Payroll;

use App\Contracts\ListGenerator;
use App\Http\Resources\Employee\Payroll\SalaryTemplateResource;
use App\Models\Employee\Payroll\SalaryTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalaryTemplateListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'alias'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('employee.payroll.salary_template.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'alias',
                'label' => trans('employee.payroll.salary_template.props.alias'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'salaryStructureCount',
                'label' => trans('employee.payroll.salary_structure.salary_structure'),
                'print_label' => 'structures_count',
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
        return SalaryTemplate::query()
            ->when($request->query('record'), function ($q) {
                $q->with('records', 'records.payHead');
            })
            ->withCount('structures')
            ->byTeam()
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:alias',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return SalaryTemplateResource::collection($this->filter($request)
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
