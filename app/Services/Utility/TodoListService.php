<?php

namespace App\Services\Utility;

use App\Contracts\ListGenerator;
use App\Http\Resources\Utility\TodoResource;
use App\Models\Utility\Todo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TodoListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'due_date', 'title', 'completed_at'];

    protected $defaultSort = 'due_date';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'title',
                'label' => trans('utility.todo.props.title'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'due',
                'label' => trans('utility.todo.due'),
                'print_label' => 'due.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'status',
                'label' => trans('utility.todo.status'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'completedAt',
                'label' => trans('utility.todo.completed_at'),
                'print_label' => 'completed_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'statusUpdate',
                'label' => '',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        return Todo::query()
            ->with('list')
            ->whereUserId(auth()->id())
            ->filterByStatus($request->status)
            ->when($request->boolean('is_archived'), function ($q) {
                $q->whereNotNull('archived_at');
            }, function ($q) {
                $q->whereNull('archived_at');
            })
            ->filter([
                'App\QueryFilters\LikeMatch:search,title,description',
                'App\QueryFilters\DateBetween:start_date,end_date,due_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return TodoResource::collection($this->filter($request)
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

    public function getIds(Request $request): array
    {
        return $this->filter($request)->select('uuid')->get()->pluck('uuid')->all();
    }
}
