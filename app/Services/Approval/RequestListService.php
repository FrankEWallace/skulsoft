<?php

namespace App\Services\Approval;

use App\Contracts\ListGenerator;
use App\Enums\Approval\Status;
use App\Http\Resources\Approval\RequestListResource as ApprovalRequestListResource;
use App\Models\Approval\Request as ApprovalRequest;
use App\Models\Employee\Employee;
use App\Models\RequestRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'title', 'date'];

    protected $defaultSort = 'code_number';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('approval.request.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'type.team.code',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'team',
                'label' => trans('team.team'),
                'print_label' => 'type.team.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'title',
                'label' => trans('approval.request.props.title'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('approval.type.type'),
                'print_label' => 'type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'group',
                'label' => trans('approval.request.group.group'),
                'print_label' => 'group.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'nature',
                'label' => trans('approval.request.nature.nature'),
                'print_label' => 'nature.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'category',
                'label' => trans('approval.type.props.category'),
                'print_label' => 'type.category.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'priority',
                'label' => trans('approval.request.priority.priority'),
                'print_label' => 'priority.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('approval.request.props.date'),
                'print_label' => 'date.formatted',
                'print_sub_label' => 'due_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'amount',
                'label' => trans('approval.request.props.amount'),
                'print_label' => 'amount.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'status',
                'label' => trans('approval.request.props.status'),
                'print_label' => 'status.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'requester',
                'label' => trans('approval.request.props.requester'),
                'print_label' => 'requester.name',
                'print_sub_label' => 'requester.designation',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'approver',
                'label' => trans('approval.request.props.approver'),
                'print_label' => 'approver.name',
                'print_sub_label' => 'approver.designation',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        if (request()->query('processed_requests')) {
            array_push($headers, [
                'key' => 'processedStatus',
                'label' => trans('approval.request.props.processed_status'),
                'print_label' => 'processed_status.label',
                'sortable' => false,
                'visibility' => true,
            ]);

            array_push($headers, [
                'key' => 'processedAt',
                'label' => trans('approval.request.props.processed_at'),
                'print_label' => 'processed_at.formatted',
                'sortable' => false,
                'visibility' => true,
            ]);

            array_push($headers, [
                'key' => 'comment',
                'label' => trans('approval.request.props.comment'),
                'print_label' => 'comment_short',
                'sortable' => false,
                'visibility' => true,
            ]);
        }

        array_push($headers, [
            'key' => 'createdAt',
            'label' => trans('general.created_at'),
            'print_label' => 'created_at.formatted',
            'sortable' => true,
            'visibility' => true,
        ]);

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $employee = null;
        if ($request->query('processed_requests')) {
            $employee = Employee::query()
                ->auth()
                ->first();

            if (! $employee) {
                throw ValidationException::withMessages([
                    'message' => trans('user.errors.permission_denied'),
                ]);
            }

            $request->merge([
                'employee_user_id' => $employee->user_id,
            ]);
        }

        $scope = $request->query('scope', 'all');
        $employees = Str::toArray($request->query('employees'));

        $employeeUserIds = Employee::query()
            ->leftJoin('contacts', 'contacts.id', '=', 'employees.contact_id')
            ->whereIn('employees.uuid', $employees)
            ->pluck('user_id')
            ->toArray();

        return ApprovalRequest::query()
            ->with(['type.team', 'type.levels', 'priority', 'requestRecords', 'group', 'nature'])
            // ->byTeam() other team members can see other team requests if they are in the approval levels
            ->filterAccessible()
            ->addSelect([
                'actionable_user_id' => RequestRecord::select('user_id')
                    ->where('model_type', 'ApprovalRequest')
                    ->whereColumn('model_id', 'approval_requests.id')
                    ->whereIn('status', [Status::REQUESTED->value, Status::HOLD->value])
                    ->orderBy('id')
                    ->limit(1),
            ])
            ->when($scope === 'self', function ($q) {
                $q->where('request_user_id', auth()->id());
            })
            ->when($request->query('processed_requests'), function ($q) {
                $q->whereHas('requestRecords', function ($q) {
                    $q->where('user_id', request()->query('employee_user_id'))
                        ->whereIn('status', [
                            Status::APPROVED->value,
                            Status::REJECTED->value,
                            Status::CANCELLED->value,
                        ]);
                });
            })
            ->when($request->query('category'), function ($q, $category) {
                $q->whereHas('type', function ($q) use ($category) {
                    $q->where('category', $category);
                });
            })
            ->when($request->query('type'), function ($q, $type) {
                $q->whereHas('type', function ($q) use ($type) {
                    $q->where('uuid', $type);
                });
            })
            ->when($request->query('priority'), function ($q, $priority) {
                $q->whereHas('priority', function ($q) use ($priority) {
                    $q->where('uuid', $priority);
                });
            })
            ->when($employeeUserIds, function ($q, $employeeUserIds) {
                $q->whereIn('request_user_id', $employeeUserIds);
            })
            ->when($request->query('pending_requests'), function ($q) {
                $q->whereIn('status', [Status::REQUESTED->value, Status::HOLD->value, Status::RETURNED->value])
                    ->havingRaw('actionable_user_id = ?', [auth()->id()]);
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\ExactMatch:code_number',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $approvalRequests = $this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        $requestUserIds = $approvalRequests->pluck('request_user_id')->unique()->toArray();

        $actionableUserIds = $approvalRequests->pluck('actionable_user_id')->unique()->toArray();

        $currentEmployee = Employee::query()
            ->auth()
            ->first();

        $date = today()->toDateString();

        $employees = Employee::query()
            ->summary($date, true)
            ->whereIn('user_id', array_merge($requestUserIds, $actionableUserIds))
            ->get();

        $request->merge([
            'current_employee' => $currentEmployee,
            'employees' => $employees,
        ]);

        return ApprovalRequestListResource::collection($approvalRequests)
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
