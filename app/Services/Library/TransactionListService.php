<?php

namespace App\Services\Library;

use App\Contracts\ListGenerator;
use App\Http\Resources\Library\TransactionListResource;
use App\Models\Employee\Employee;
use App\Models\Library\Transaction;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'issue_date', 'due_date'];

    protected $defaultSort = 'issue_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('library.transaction.props.code_number_short'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'to',
                'label' => trans('library.transaction.props.to'),
                'print_label' => 'to.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'requester',
                'label' => trans('library.transaction.props.requester'),
                'print_label' => 'requester.name',
                'print_sub_label' => 'requester.contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'requesterDetail',
                'label' => trans('library.transaction.props.requester_detail'),
                'print_label' => 'requester_detail',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'issueDate',
                'label' => trans('library.transaction.props.issue_date'),
                'print_label' => 'issue_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'dueDate',
                'label' => trans('library.transaction.props.due_date'),
                'print_label' => 'due_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'dueInDays',
                'label' => trans('library.transaction.props.due_in_days'),
                'print_label' => 'due_in_days',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'recordsCount',
                'label' => trans('library.transaction.count'),
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
        return Transaction::query()
            ->byTeam()
            ->withCount([
                'records',
                'records as non_returned_books_count' => function ($query) {
                    $query->whereNull('return_date');
                },
            ])
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\DateBetween:issue_start_date,issue_end_date,issue_date',
                'App\QueryFilters\DateBetween:due_start_date,due_end_date,due_date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $transactions = $this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        $studentIds = $transactions->filter(function ($transaction) {
            return $transaction->transactionable_type === 'Student';
        })->pluck('transactionable_id');

        $employeeIds = $transactions->filter(function ($transaction) {
            return $transaction->transactionable_type === 'Employee';
        })->pluck('transactionable_id');

        $students = Student::query()
            ->summary()
            ->whereIn('students.id', $studentIds)
            ->get();

        $employees = Employee::query()
            ->summary()
            ->whereIn('employees.id', $employeeIds)
            ->get();

        $request->merge([
            'students' => $students,
            'employees' => $employees,
        ]);

        return TransactionListResource::collection($transactions)
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
