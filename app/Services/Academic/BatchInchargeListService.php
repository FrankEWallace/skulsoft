<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\BatchInchargeResource;
use App\Models\Academic\Batch;
use App\Models\Incharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BatchInchargeListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'start_date'];

    protected $defaultSort = 'start_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'batch',
                'label' => trans('academic.batch.batch'),
                'print_label' => 'batch.course.name',
                'print_sub_label' => 'batch.name',
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
                'key' => 'period',
                'label' => trans('employee.incharge.props.period'),
                'print_label' => 'period',
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
        $batches = Str::toArray($request->query('batches'));
        $employees = Str::toArray($request->query('employees'));

        return Incharge::query()
            ->whereHasMorph(
                'model',
                [Batch::class],
                function (Builder $query) use ($batches) {
                    $query->byPeriod()
                        ->when($batches, function ($q, $batches) {
                            $q->whereIn('uuid', $batches);
                        });
                }
            )
            ->with(['model.course', 'employee' => fn ($q) => $q->summary()])
            ->when($employees, function ($q, $employees) {
                $q->whereHas('employee', function ($q) use ($employees) {
                    $q->whereIn('uuid', $employees);
                });
            })
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return BatchInchargeResource::collection($this->filter($request)
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
