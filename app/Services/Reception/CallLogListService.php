<?php

namespace App\Services\Reception;

use App\Contracts\ListGenerator;
use App\Http\Resources\Reception\CallLogResource;
use App\Models\Reception\CallLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CallLogListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'type',
                'label' => trans('reception.call_log.props.type'),
                'print_label' => 'type.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'purpose',
                'label' => trans('reception.call_log.props.purpose'),
                'print_label' => 'purpose.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('reception.call_log.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'company_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'incomingNumber',
                'label' => trans('reception.call_log.props.incoming_number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'outgoingNumber',
                'label' => trans('reception.call_log.props.outgoing_number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'callAt',
                'label' => trans('reception.call_log.props.call_at'),
                'print_label' => 'call_at.formatted',
                'print_sub_label' => 'duration.label',
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
        $purposes = Str::toArray($request->query('purposes'));

        return CallLog::query()
            ->byTeam()
            ->with('purpose')
            ->when($purposes, function ($q, $purposes) {
                $q->whereHas('purpose', function ($q) use ($purposes) {
                    $q->whereIn('uuid', $purposes);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:incoming_number',
                'App\QueryFilters\LikeMatch:outgoing_number',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,call_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return CallLogResource::collection($this->filter($request)
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
