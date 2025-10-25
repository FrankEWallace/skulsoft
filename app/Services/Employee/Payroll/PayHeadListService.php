<?php

namespace App\Services\Employee\Payroll;

use App\Contracts\ListGenerator;
use App\Http\Resources\Employee\Payroll\PayHeadResource;
use App\Models\Employee\Payroll\PayHead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayHeadListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'code', 'alias', 'type', 'position'];

    protected $defaultSort = 'position';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('employee.payroll.pay_head.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'code',
                'label' => trans('employee.payroll.pay_head.props.code'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'alias',
                'label' => trans('employee.payroll.pay_head.props.alias'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'category',
                'label' => trans('employee.payroll.pay_head.props.category'),
                'print_label' => 'category.label',
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
        return PayHead::query()
            ->byTeam()
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:code',
                'App\QueryFilters\LikeMatch:alias',
                'App\QueryFilters\ExactMatch:category',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return PayHeadResource::collection($this->filter($request)
                ->orderBy('position', 'asc')
                ->get());
        }

        return PayHeadResource::collection($this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'title' => 'PayHead',
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
