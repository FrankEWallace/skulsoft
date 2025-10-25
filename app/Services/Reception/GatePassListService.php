<?php

namespace App\Services\Reception;

use App\Contracts\ListGenerator;
use App\Http\Resources\Reception\GatePassResource;
use App\Models\Reception\GatePass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class GatePassListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('reception.gate_pass.props.code_number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'startAt',
                'label' => trans('reception.gate_pass.props.datetime'),
                'print_label' => 'start_at.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'to',
                'label' => trans('reception.gate_pass.props.to'),
                'print_label' => 'requester_type.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'requester',
                'label' => trans('reception.gate_pass.props.requester'),
                // 'print_label' => 'requester.name',
                // 'print_sub_label' => 'requester.contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'purpose',
                'label' => trans('reception.gate_pass.props.purpose'),
                'print_label' => 'purpose.name',
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
        $purposes = Str::toArray($request->query('purposes'));

        return GatePass::query()
            ->byTeam()
            ->with([
                'purpose',
                'audiences.audienceable.contact',
            ])
            ->when($purposes, function ($q, $purposes) {
                $q->whereHas('purpose', function ($q) use ($purposes) {
                    $q->whereIn('uuid', $purposes);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,start_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return GatePassResource::collection($this->filter($request)
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
