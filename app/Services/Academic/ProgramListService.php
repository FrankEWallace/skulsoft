<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\ProgramResource;
use App\Models\Academic\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProgramListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'alias', 'position'];

    protected $defaultSort = 'position';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('academic.program.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'type.name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'department',
                'label' => trans('academic.department.department'),
                'print_label' => 'department.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'registration',
                'label' => trans('student.registration.registration'),
                'print_label' => 'enable_registration',
                'type' => 'boolean',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'code',
                'label' => trans('academic.program.props.code'),
                'print_label' => 'code',
                'print_sub_label' => 'shortcode',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'alias',
                'label' => trans('academic.program.props.alias'),
                'print_label' => 'alias',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'incharge',
                'label' => trans('academic.program_incharge.program_incharge'),
                'print_label' => 'incharges',
                'print_key' => 'employee.name',
                'print_sub_key' => 'period',
                'type' => 'array',
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
        $details = $request->query('details');

        return Program::query()
            ->with('department', 'type')
            ->when($details, function ($q) {
                $q->withCurrentIncharges();
            })
            ->byTeam()
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:code',
                'App\QueryFilters\LikeMatch:shortcode',
                'App\QueryFilters\LikeMatch:alias',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return ProgramResource::collection($this->filter($request)
                ->orderBy('position', 'asc')
                ->get());
        }

        return ProgramResource::collection($this->filter($request)
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
