<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\ClassTimingResource;
use App\Models\Academic\ClassTiming;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClassTimingListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('academic.class_timing.class_timing'),
                'print_label' => 'name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'duration',
                'label' => trans('academic.class_timing.props.duration'),
                'print_label' => 'duration',
                'print_sub_label' => 'period',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'session',
                'label' => trans('academic.class_timing.session'),
                'print_label' => 'session_count',
                'print_sub_label' => 'break_count',
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
        return ClassTiming::query()
            ->byPeriod()
            ->with('sessions')
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return ClassTimingResource::collection($this->filter($request)
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
