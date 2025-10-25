<?php

namespace App\Services\Communication;

use App\Contracts\ListGenerator;
use App\Http\Resources\Communication\AnnouncementResource;
use App\Models\Communication\Announcement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class AnnouncementListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'published_at'];

    protected $defaultSort = 'published_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('communication.announcement.props.code_number'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'title',
                'label' => trans('communication.announcement.props.title'),
                'print_label' => 'title_excerpt',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('communication.announcement.type.type'),
                'print_label' => 'type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'employee',
                'label' => trans('employee.employee'),
                'print_label' => 'employee.name',
                'print_sub_label' => 'employee.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'audience',
                'label' => trans('communication.announcement.props.audience'),
                'type' => 'array',
                'print_label' => 'audience_types',
                'sortable' => false,
                'visibility' => true,
            ],
            // [
            //     'key' => 'publishedAt',
            //     'label' => trans('communication.announcement.props.published_at'),
            //     'print_label' => 'published_at.formatted',
            //     'sortable' => false,
            //     'visibility' => true,
            // ],
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

        return Announcement::query()
            ->byPeriod()
            ->filterAccessible()
            ->with(['type', 'employee' => fn ($q) => $q->summary()])
            ->when($types, function ($q, $types) {
                $q->whereHas('type', function ($q) use ($types) {
                    $q->whereIn('uuid', $types);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\LikeMatch:search,title,description',
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,published_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return AnnouncementResource::collection($this->filter($request)
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
