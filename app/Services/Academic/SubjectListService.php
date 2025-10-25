<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class SubjectListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'alias', 'code', 'position'];

    protected $defaultSort = 'position';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('academic.subject.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'type.name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'alias',
                'label' => trans('academic.subject.props.alias'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'code',
                'label' => trans('academic.subject.props.code'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'shortcode',
                'label' => trans('academic.subject.props.shortcode'),
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
        $types = Str::toArray($request->query('types'));

        return Subject::query()
            ->byPeriod()
            ->with('type')
            ->when($types, function ($q, $types) {
                $q->whereHas('type', function ($q) use ($types) {
                    $q->whereIn('uuid', $types);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:alias',
                'App\QueryFilters\LikeMatch:code',
                'App\QueryFilters\LikeMatch:shortcode',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return SubjectResource::collection($this->filter($request)
                ->orderBy('position', 'asc')
                ->get());
        }

        return SubjectResource::collection($this->filter($request)
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
