<?php

namespace App\Services\Exam;

use App\Contracts\ListGenerator;
use App\Http\Resources\Exam\ExamResource;
use App\Models\Exam\Exam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExamListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'position'];

    protected $defaultSort = 'position';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('exam.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'code',
                'print_additional_label' => 'display_name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'division',
                'label' => trans('academic.division.division'),
                'print_label' => 'division.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'term',
                'label' => trans('exam.term.term'),
                'print_label' => 'term.name',
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
        return Exam::query()
            ->byPeriod()
            ->with('term.division')
            ->filter([
                'App\QueryFilters\LikeMatch:name',
                'App\QueryFilters\LikeMatch:code',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return ExamResource::collection($this->filter($request)
                ->orderBy('position', 'asc')
                ->get());
        }

        return ExamResource::collection($this->filter($request)
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
