<?php

namespace App\Services\Inventory;

use App\Contracts\ListGenerator;
use App\Enums\Finance\LedgerGroup;
use App\Http\Resources\Finance\LedgerResource;
use App\Models\Finance\Ledger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorListService extends ListGenerator
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
                'print_additional_label' => 'type.name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'contact_number',
                'label' => trans('contact.props.contact_number'),
                'print_label' => 'contact_number',
                'print_sub_label' => 'email',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'address',
                'label' => trans('contact.props.address.address'),
                'print_label' => 'address_display',
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
        return Ledger::query()
            ->byTeam()
            ->whereHas('type', function ($q) {
                $q->where('type', LedgerGroup::SUNDRY_CREDITOR);
            })
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
