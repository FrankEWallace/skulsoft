<?php

namespace App\Services\Hostel;

use App\Contracts\ListGenerator;
use App\Http\Resources\Hostel\BlockResource;
use App\Models\Hostel\Block;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlockListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'alias'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('hostel.block.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'alias',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'address',
                'label' => trans('hostel.block.props.address'),
                'print_label' => 'address',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'contact',
                'label' => trans('hostel.block.props.contact_number'),
                'print_label' => 'contact_number',
                'print_sub_label' => 'contact_email',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'incharge',
                'label' => trans('hostel.block_incharge.block_incharge'),
                'print_label' => 'incharges',
                'print_key' => 'employee.name',
                'type' => 'array',
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
        $details = $request->query('details');

        return Block::query()
            ->byTeam()
            ->hostel()
            ->when($details, function ($q) {
                $q->withCurrentIncharges();
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:alias',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return BlockResource::collection($this->filter($request)
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
