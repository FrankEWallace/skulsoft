<?php

namespace App\Services\Library\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Library\Report\TopBorrowerListResource;
use App\Models\Employee\Employee;
use App\Models\Library\Transaction;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TopBorrowerListService extends ListGenerator
{
    protected $allowedSorts = ['count'];

    protected $defaultSort = 'count';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'sno',
                'label' => trans('general.sno'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'issuedTo',
                'label' => trans('library.transaction.props.to'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'requester',
                'label' => trans('library.transaction.props.requester'),
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
                'key' => 'count',
                'label' => trans('library.transaction.count'),
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        // if (request()->ajax()) {
        //     $headers[] = $this->actionHeader;
        // }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        return Transaction::query()
            ->select('transactionable_type', 'transactionable_id', \DB::raw('COUNT(*) as count'))
            ->when($request->query('issued_to') == 'student', function ($q) {
                return $q->where('transactionable_type', 'Student');
            })
            ->when($request->query('issued_to') == 'employee', function ($q) {
                return $q->where('transactionable_type', 'Employee');
            })
            ->groupBy('transactionable_type', 'transactionable_id')
            ->orderByDesc('count')
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,issue_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $transactions = $this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->when($request->query('output') == 'export_all_excel', function ($q) {
                return $q->get();
            }, function ($q) {
                return $q->paginate((int) $this->getPageLength(), ['*'], 'current_page');
            });

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

        return TopBorrowerListResource::collection($transactions)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Top Borrower Report',
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
