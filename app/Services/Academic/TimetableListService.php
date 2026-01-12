<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\TimetableResource;
use App\Domain\Academic\Models\Timetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TimetableListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'effective_date'];

    protected $defaultSort = 'effective_date';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'batch',
                'label' => trans('academic.batch.batch'),
                'print_label' => 'batch.course.name_with_term',
                'print_sub_label' => 'batch.name',
                'print_additional_label' => 'room.full_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'effectiveDate',
                'label' => trans('academic.timetable.props.effective_date'),
                'print_label' => 'effective_date.formatted',
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

        return Timetable::query()
            ->filterAccessible()
            ->with(['batch.course', 'room' => fn ($q) => $q->withFloorAndBlock()])
            ->when($batches, function ($query) use ($batches) {
                $query->whereHas('batch', function ($query) use ($batches) {
                    $query->whereIn('uuid', $batches);
                });
            })
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,effective_date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return TimetableResource::collection($this->filter($request)
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
