<?php

namespace App\Services\Reception;

use App\Contracts\ListGenerator;
use App\Http\Resources\Reception\VisitorLogResource;
use App\Models\Reception\VisitorLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class VisitorLogListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('reception.visitor_log.props.code_number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'entryAt',
                'label' => trans('reception.visitor_log.props.entry_at'),
                'print_label' => 'entry_at.formatted',
                'print_label' => 'exit_at.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('reception.visitor_log.props.type'),
                'print_label' => 'type.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('reception.visitor_log.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'purpose',
                'label' => trans('reception.visitor_log.props.purpose'),
                'print_label' => 'purpose.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'employee',
                'label' => trans('reception.visitor_log.props.whom_to_meet'),
                'print_label' => 'employee.name',
                'print_sub_label' => 'employee.designation',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'count',
                'label' => trans('reception.visitor_log.props.count'),
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
        $purposes = Str::toArray($request->query('purposes'));

        return VisitorLog::query()
            ->byTeam()
            ->with([
                'purpose',
                'visitor.contact',
                'employee' => fn ($q) => $q->summary(),
            ])
            ->when($purposes, function ($q, $purposes) {
                $q->whereHas('purpose', function ($q) use ($purposes) {
                    $q->whereIn('uuid', $purposes);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,entry_at,datetime',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return VisitorLogResource::collection($this->filter($request)
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
