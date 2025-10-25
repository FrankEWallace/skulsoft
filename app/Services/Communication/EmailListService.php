<?php

namespace App\Services\Communication;

use App\Contracts\ListGenerator;
use App\Enums\Communication\Type;
use App\Http\Resources\Communication\EmailResource;
use App\Models\Communication\Communication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmailListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'subject',
                'label' => trans('communication.email.props.subject'),
                'print_label' => 'subject',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'audience',
                'label' => trans('communication.email.props.audience'),
                'type' => 'array',
                'print_label' => 'audience_types',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'recipientCount',
                'label' => trans('communication.email.props.recipient'),
                'print_label' => 'recipient_count',
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
        return Communication::query()
            ->select('id', 'uuid', 'period_id', 'type', 'subject', 'lists', 'user_id', 'audience', 'meta', 'created_at', 'updated_at')
            ->byPeriod()
            ->filterAccessible()
            ->where('type', Type::EMAIL)
            ->filter([
                'App\QueryFilters\LikeMatch:subject',
                'App\QueryFilters\DateBetween:start_date,end_date,created_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return EmailResource::collection($this->filter($request)
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
