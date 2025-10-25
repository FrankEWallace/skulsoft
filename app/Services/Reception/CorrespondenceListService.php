<?php

namespace App\Services\Reception;

use App\Contracts\ListGenerator;
use App\Http\Resources\Reception\CorrespondenceResource;
use App\Models\Reception\Correspondence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CorrespondenceListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'type',
                'label' => trans('reception.correspondence.props.type'),
                'print_label' => 'type.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'letterNumber',
                'label' => trans('reception.correspondence.props.letter_number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'mode',
                'label' => trans('reception.correspondence.props.mode'),
                'print_label' => 'mode.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'sender',
                'label' => trans('reception.correspondence.props.sender'),
                'print_label' => 'sender_title',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'receiver',
                'label' => trans('reception.correspondence.props.receiver'),
                'print_label' => 'receiver_title',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('reception.correspondence.props.date'),
                'print_label' => 'date.formatted',
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
        return Correspondence::query()
            ->byTeam()
            ->filter([
                'App\QueryFilters\LikeMatch:letter_number',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return CorrespondenceResource::collection($this->filter($request)
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
