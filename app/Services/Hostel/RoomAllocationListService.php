<?php

namespace App\Services\Hostel;

use App\Contracts\ListGenerator;
use App\Http\Resources\Hostel\RoomAllocationResource;
use App\Models\Hostel\RoomAllocation;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class RoomAllocationListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'start_date'];

    protected $defaultSort = 'start_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'room',
                'label' => trans('hostel.room.room'),
                'print_label' => 'room.full_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'student',
                'label' => trans('student.student'),
                'print_label' => 'student.name',
                'print_sub_label' => 'student.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'period',
                'label' => trans('hostel.room_allocation.props.period'),
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
        $rooms = Str::toArray($request->query('rooms'));
        $students = Str::toArray($request->query('students'));

        return RoomAllocation::query()
            ->whereHasMorph(
                'model',
                [Student::class],
                function (Builder $query) use ($students) {
                    $query->when($students, function ($q, $students) {
                        $q->whereIn('uuid', $students);
                    });
                }
            )
            ->with(['room' => fn ($q) => $q->withFloorAndBlock(), 'model' => fn ($q) => $q->summary()])
            ->when($rooms, function ($q, $rooms) {
                $q->whereHas('room', function ($q) use ($rooms) {
                    $q->whereIn('uuid', $rooms);
                });
            })
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return RoomAllocationResource::collection($this->filter($request)
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
