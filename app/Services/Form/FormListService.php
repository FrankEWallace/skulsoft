<?php

namespace App\Services\Form;

use App\Contracts\ListGenerator;
use App\Http\Resources\Form\FormResource;
use App\Models\Form\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FormListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('form.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'summary',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'dueDate',
                'label' => trans('form.props.due_date'),
                'print_label' => 'due_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (auth()->user()->can('form-submission:manage')) {
            $headers[] = [
                'key' => 'status',
                'label' => trans('form.props.status'),
                'print_label' => 'status.label',
                'sortable' => false,
                'visibility' => true,
            ];

            $headers[] = [
                'key' => 'submissions',
                'label' => trans('form.submission.submission'),
                'print_label' => 'submission_count',
                'sortable' => false,
                'visibility' => true,
            ];

            $headers[] = [
                'key' => 'createdAt',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ];
        }

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $permission = auth()->user()->can('form-submission:manage');

        return Form::query()
            ->byPeriod()
            ->filterAccessible()
            ->withSubmission()
            ->when($permission, function ($q) {
                $q->withCount('submissions');
            })
            ->when(! $permission, function ($q) {
                $q->where('published_at', '<=', now()->toDateTimeString());
            })
            ->filter([
                'App\QueryFilters\LikeMatch:search,name,summary,description',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,due_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return FormResource::collection($this->filter($request)
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
