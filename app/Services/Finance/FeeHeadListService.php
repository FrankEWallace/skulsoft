<?php

namespace App\Services\Finance;

use App\Contracts\ListGenerator;
use App\Http\Resources\Finance\FeeHeadResource;
use App\Models\Finance\FeeHead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class FeeHeadListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('finance.fee_head.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'feeGroup',
                'label' => trans('finance.fee_group.fee_group'),
                'print_label' => 'group.name',
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
        $feeGroups = Str::toArray($request->query('fee_groups'));

        return FeeHead::query()
            ->with('group')
            ->byPeriod()
            ->when($feeGroups, function ($q, $feeGroups) {
                $q->whereHas('group', function ($q) use ($feeGroups) {
                    $q->whereIn('uuid', $feeGroups);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return FeeHeadResource::collection($this->filter($request)
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
