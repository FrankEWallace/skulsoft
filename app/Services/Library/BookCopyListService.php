<?php

namespace App\Services\Library;

use App\Contracts\ListGenerator;
use App\Http\Resources\Library\BookCopyListResource;
use App\Models\Employee\Employee;
use App\Models\Library\Book;
use App\Models\Library\BookCopy;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookCopyListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'book_copies.created_at';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'number',
                'label' => trans('library.book_copy.props.number_short'),
                'print_label' => 'number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'book',
                'label' => trans('library.book.book'),
                'print_label' => 'book.title',
                'print_sub_label' => 'book.sub_title',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'issueStatus',
                'label' => trans('library.transaction.props.issue_status'),
                'print_label' => 'issue_status.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'transactionCodeNumber',
                'label' => trans('library.transaction.props.code_number'),
                'print_label' => 'transaction_code_number',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'issuedTo',
                'label' => trans('library.transaction.props.to'),
                'print_label' => 'issued_to.label',
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
                'key' => 'copiesCount',
                'label' => trans('library.book_addition.props.copies'),
                'print_label' => 'copies_count',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'holdStatus',
                'label' => trans('library.book_copy.props.status'),
                'print_label' => 'hold_status.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'category',
                'label' => trans('library.book.props.category'),
                'print_label' => 'book.category.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'author',
                'label' => trans('library.book.props.author'),
                'print_label' => 'book.author.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'publisher',
                'label' => trans('library.book.props.publisher'),
                'print_label' => 'book.publisher.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'topic',
                'label' => trans('library.book.props.topic'),
                'print_label' => 'book.topic.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'language',
                'label' => trans('library.book.props.language'),
                'print_label' => 'book.language.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'isbnNumber',
                'label' => trans('library.book.props.isbn_number'),
                'print_label' => 'book.isbn_number',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'callNumber',
                'label' => trans('library.book.props.call_number'),
                'print_label' => 'book.call_number',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'yearPublished',
                'label' => trans('library.book.props.year_published'),
                'print_label' => 'book.year_published',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'subject',
                'label' => trans('library.book.props.subject'),
                'print_label' => 'book.subject',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'volume',
                'label' => trans('library.book.props.volume'),
                'print_label' => 'book.volume',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'edition',
                'label' => trans('library.book.props.edition'),
                'print_label' => 'book.edition',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'page',
                'label' => trans('library.book.props.page'),
                'print_label' => 'book.page',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'price',
                'label' => trans('library.book.props.price'),
                'print_label' => 'book.price.formatted',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'additionDate',
                'label' => trans('library.book_addition.props.date'),
                'print_label' => 'book_addition_date.formatted',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'vendor',
                'label' => trans('library.book_addition.props.vendor'),
                'print_label' => 'vendor',
                'print_sub_label' => 'invoice_number',
                'print_sub_label_2' => 'invoice_date',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'invoiceNumber',
                'label' => trans('library.book_addition.props.invoice_number'),
                'print_label' => 'invoice_number',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'invoiceDate',
                'label' => trans('library.book_addition.props.invoice_date'),
                'print_label' => 'invoice_date.formatted',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'location',
                'label' => trans('library.book_addition.props.location'),
                'print_label' => 'location',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'roomNumber',
                'label' => trans('library.book_addition.props.room_number'),
                'print_label' => 'room_number',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'rackNumber',
                'label' => trans('library.book_addition.props.rack_number'),
                'print_label' => 'rack_number',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'shelfNumber',
                'label' => trans('library.book_addition.props.shelf_number'),
                'print_label' => 'shelf_number',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'condition',
                'label' => trans('library.book_condition.book_condition'),
                'print_label' => 'condition.name',
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
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $date = $request->query('date', today()->format('Y-m-d'));

        return BookCopy::query()
            ->with('condition')
            ->leftJoin('book_additions', 'book_additions.id', '=', 'book_copies.book_addition_id')
            ->leftJoin('books', 'books.id', '=', 'book_copies.book_id')
            ->leftJoin(\DB::raw('
            (
                SELECT book_transaction_records.*
                FROM book_transaction_records
                INNER JOIN (
                    SELECT book_copy_id, MAX(id) as latest_record_id
                    FROM book_transaction_records
                    GROUP BY book_copy_id
                ) as latest_record ON book_transaction_records.id = latest_record.latest_record_id WHERE book_transaction_records.return_date IS NULL
            ) as latest_book_transaction_records
            '), 'latest_book_transaction_records.book_copy_id', '=', 'book_copies.id')
            ->leftJoin('book_transactions', 'book_transactions.id', '=', 'latest_book_transaction_records.book_transaction_id')
            ->where(function ($query) use ($date) {
                $query->whereNull('book_transactions.issue_date')
                    ->orWhereDate('book_transactions.issue_date', '<=', $date);
            })
            ->where('books.team_id', auth()->user()->current_team_id)
            ->select(
                'book_copies.*',
                'book_transactions.code_number as transaction_code_number',
                'book_transactions.transactionable_type',
                'book_transactions.transactionable_id',
                'book_transactions.issue_date',
                'book_transactions.due_date',
                'book_additions.date as book_addition_date',
                'latest_book_transaction_records.return_date'
            )
            ->when($request->query('number'), function ($query) use ($request) {
                $query->where('book_copies.number', '=', $request->query('number'));
            })
            ->when($request->query('title'), function ($query) use ($request) {
                $query->whereHas('book', function ($query) use ($request) {
                    $query->where('title', 'like', '%'.$request->query('title').'%');
                });
            })
            ->filter([
                'App\QueryFilters\DateBetween:issue_start_date,issue_end_date,issue_date',
                'App\QueryFilters\DateBetween:due_start_date,due_end_date,due_date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $bookCopies = $this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->when($request->query('output') == 'export_all_excel', function ($q) {
                return $q->get();
            }, function ($q) {
                return $q->paginate((int) $this->getPageLength(), ['*'], 'current_page');
            });

        $bookIds = $bookCopies->pluck('book_id');

        $studentIds = $bookCopies->filter(function ($bookCopy) {
            return $bookCopy->transactionable_type === 'Student';
        })->pluck('transactionable_id');

        $employeeIds = $bookCopies->filter(function ($bookCopy) {
            return $bookCopy->transactionable_type === 'Employee';
        })->pluck('transactionable_id');

        $students = Student::query()
            ->summary()
            ->whereIn('students.id', $studentIds)
            ->get();

        $employees = Employee::query()
            ->summary()
            ->whereIn('employees.id', $employeeIds)
            ->get();

        $books = Book::query()
            ->withCount('copies')
            ->with('category', 'author', 'publisher', 'topic', 'language')
            ->whereIn('id', $bookIds)
            ->get();

        $date = $request->query('date', today()->format('Y-m-d'));

        $request->merge([
            'date' => $date,
            'students' => $students,
            'employees' => $employees,
            'books' => $books,
        ]);

        return BookCopyListResource::collection($bookCopies)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Library Book Copy List',
                    'sno' => $this->getSno(),
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
