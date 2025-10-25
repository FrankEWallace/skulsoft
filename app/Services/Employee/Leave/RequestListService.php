<?php

namespace App\Services\Employee\Leave;

use App\Concerns\SubordinateAccess;
use App\Contracts\ListGenerator;
use App\Http\Resources\Employee\Leave\RequestResource as LeaveRequestResource;
use App\Models\Employee\Employee;
use App\Models\Employee\Leave\Request as LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class RequestListService extends ListGenerator
{
    use SubordinateAccess;

    protected $allowedSorts = ['created_at', 'start_date', 'end_date'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'employee',
                'label' => trans('employee.employee'),
                'print_label' => 'employee.full_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'leaveType',
                'label' => trans('employee.leave.type.type'),
                'print_label' => 'leave_type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'startDate',
                'label' => trans('employee.leave.request.props.start_date'),
                'print_label' => 'start_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('employee.leave.request.props.end_date'),
                'print_label' => 'end_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'status',
                'label' => trans('employee.leave.request.props.status'),
                'print_label' => 'status_display',
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
        $accessibleEmployeeIds = $this->getAccessibleEmployeeIds();

        $employees = Str::toArray($request->query('employees'));
        $leaveTypes = Str::toArray($request->query('leave_types'));

        return LeaveRequest::query()
            ->with(['model' => fn ($q) => $q->summary(), 'type'])
            ->byTeam()
            ->whereHasMorph(
                'model', [Employee::class],
                function ($q) use ($accessibleEmployeeIds, $employees) {
                    $q->whereIn('id', $accessibleEmployeeIds)->when($employees, function ($q) use ($employees) {
                        $q->whereIn('uuid', $employees);
                    });
                }
            )
            ->when($leaveTypes, function ($q, $leaveTypes) {
                $q->whereHas('type', function ($q) use ($leaveTypes) {
                    $q->whereIn('uuid', $leaveTypes);
                });
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return LeaveRequestResource::collection($this->filter($request)
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
