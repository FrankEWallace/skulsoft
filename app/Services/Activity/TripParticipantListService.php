<?php

namespace App\Services\Activity;

use App\Contracts\ListGenerator;
use App\Http\Resources\Activity\TripParticipantResource;
use App\Models\Activity\Trip;
use App\Models\Activity\TripParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TripParticipantListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'type',
                'label' => trans('activity.trip.participant.props.type'),
                'print_label' => 'type.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('activity.trip.participant.participant'),
                'print_label' => 'name',
                'print_sub_label' => 'contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'fee',
                'label' => trans('activity.trip.props.fee'),
                'print_label' => 'amount.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'balance',
                'label' => trans('activity.trip.props.balance'),
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

    public function filter(Request $request, Trip $trip): Builder
    {
        return TripParticipant::query()
            ->with('model.contact')
            ->whereTripId($trip->id)
            ->filter([
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request, Trip $trip): AnonymousResourceCollection
    {
        return TripParticipantResource::collection($this->filter($request, $trip)
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

    public function list(Request $request, Trip $trip): AnonymousResourceCollection
    {
        return $this->paginate($request, $trip);
    }
}
