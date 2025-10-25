<?php

namespace App\Services\Approval;

use App\Contracts\ListGenerator;
use App\Http\Resources\Approval\TypeResource as ApprovalTypeResource;
use App\Models\Approval\Type as ApprovalType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TypeListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('approval.type.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'status.label',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'category',
                'label' => trans('approval.type.props.category'),
                'print_label' => 'category.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'department',
                'label' => trans('employee.department.department'),
                'print_label' => 'department.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'levels',
                'label' => trans('approval.type.props.levels'),
                'type' => 'array',
                'print_label' => 'levels',
                'print_key' => 'employee.name',
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
        $date = today()->toDateString();

        return ApprovalType::query()
            ->with(['department', 'levels' => fn ($q) => $q->orderBy('position', 'asc'), 'levels.employee' => fn ($q) => $q->summary($date, true)])
            ->byTeam()
            ->when($request->query('department'), function ($q, $department) {
                $q->whereHas('department', function ($q) use ($department) {
                    $q->where('uuid', $department);
                });
            })
            ->when($request->query('category'), function ($q, $category) {
                $q->where('category', $category);
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:name',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return ApprovalTypeResource::collection($this->filter($request)
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
