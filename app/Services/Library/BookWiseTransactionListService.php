<?php

namespace App\Services\Library;

use App\Contracts\ListGenerator;
use App\Http\Resources\Library\BookWiseTransactionListResource;
use App\Models\Employee\Employee;
use App\Models\Library\TransactionRecord;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookWiseTransactionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'book_transaction_records.created_at';

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
                'key' => 'bookTitle',
                'label' => trans('library.book.props.title'),
                'print_label' => 'book_title',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'bookNumber',
                'label' => trans('library.book.props.number'),
                'print_label' => 'book_copy_number',
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
                'key' => 'returnDate',
                'label' => trans('library.transaction.props.return_date'),
                'print_label' => 'return_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'charges',
                'label' => trans('library.transaction.props.library_charge'),
                'print_label' => 'charge.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'returnStatus',
                'label' => trans('library.transaction.props.return_status'),
                'print_label' => 'return_status.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'condition',
                'label' => trans('library.book_condition.book_condition'),
                'print_label' => 'condition.name',
                'sortable' => false,
                'visibility' => false,
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
        return TransactionRecord::query()
            ->select('book_transaction_records.*', 'book_transactions.uuid as transaction_uuid', 'book_transactions.code_number', 'book_transactions.transactionable_id', 'book_transactions.transactionable_type', 'book_transactions.issue_date', 'book_transactions.due_date', 'book_copies.number as book_copy_number', 'books.title as book_title')
            ->join('book_transactions', 'book_transaction_records.book_transaction_id', '=', 'book_transactions.id')
            ->leftJoin('book_copies', 'book_transaction_records.book_copy_id', '=', 'book_copies.id')
            ->with('condition')
            ->leftJoin('books', 'book_copies.book_id', '=', 'books.id')
            ->where('book_transactions.team_id', auth()->user()->current_team_id)
            ->when($request->query('code_number'), function ($query, $codeNumber) {
                $query->where('book_transactions.code_number', '=', $codeNumber);
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\LikeMatch:code_number,book_transactions.code_number',
                'App\QueryFilters\DateBetween:issue_start_date,issue_end_date,issue_date',
                'App\QueryFilters\DateBetween:due_start_date,due_end_date,due_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $transactionRecords = $this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        $studentIds = $transactionRecords->filter(function ($transactionRecord) {
            return $transactionRecord->transactionable_type === 'Student';
        })->pluck('transactionable_id');

        $employeeIds = $transactionRecords->filter(function ($transactionRecord) {
            return $transactionRecord->transactionable_type === 'Employee';
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

        return BookWiseTransactionListResource::collection($transactionRecords)
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
