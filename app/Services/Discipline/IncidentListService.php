<?php

namespace App\Services\Discipline;

use App\Contracts\ListGenerator;
use App\Http\Resources\Discipline\IncidentResource;
use App\Models\Discipline\Incident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class IncidentListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'category',
                'label' => trans('discipline.incident.props.category'),
                'print_label' => 'category.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'title',
                'label' => trans('discipline.incident.props.title'),
                'print_label' => 'title',
                'print_sub_label' => 'nature.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('discipline.incident.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('discipline.incident.props.date'),
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
        $categories = Str::toArray($request->query('categories'));

        return Incident::query()
            ->byPeriod()
            ->filterAccessible()
            ->with('category', 'model.contact')
            ->when($categories, function ($q, $categories) {
                $q->whereHas('category', function ($q) use ($categories) {
                    $q->whereIn('uuid', $categories);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:search,title,description',
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return IncidentResource::collection($this->filter($request)
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
