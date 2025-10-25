<?php

namespace App\Services\Finance;

use App\Contracts\ListGenerator;
use App\Http\Resources\Finance\LedgerResource;
use App\Models\Finance\Ledger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class LedgerListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('finance.ledger.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'alias',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('finance.ledger_type.ledger_type'),
                'print_label' => 'type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'netBalance',
                'label' => trans('finance.ledger.props.net_balance'),
                'print_label' => 'balance.formatted',
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
        $ledgerTypes = Str::toArray($request->query('ledger_types'));
        $subType = $request->query('sub_type');

        return Ledger::query()
            ->byTeam()
            ->when($ledgerTypes, function ($q, $ledgerTypes) {
                $q->whereHas('type', function ($q) use ($ledgerTypes) {
                    $q->whereIn('uuid', $ledgerTypes);
                });
            })
            ->subType($subType)
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:alias',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return LedgerResource::collection($this->filter($request)
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
