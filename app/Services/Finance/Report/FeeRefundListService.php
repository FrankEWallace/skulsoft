<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Finance\Report\FeeRefundListResource;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeeRefundListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'serial_number', 'voucher_number', 'name', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'voucherNumber',
                'label' => trans('finance.transaction.voucher'),
                'print_label' => 'voucher_number',
                'print_sub_label' => 'reference_number',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('student.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'fatherName',
                'label' => trans('contact.props.father_name'),
                'print_label' => 'father_name',
                'print_sub_label' => 'contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                // 'print_sub_label' => 'batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'amount',
                'label' => trans('finance.transaction.props.amount'),
                'type' => 'currency',
                'print_label' => 'amount.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('finance.transaction.props.date'),
                'type' => 'date',
                'print_label' => 'date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'ledger',
                'label' => trans('finance.ledger.ledger'),
                'print_label' => 'payment.ledger.name',
                'print_sub_label' => 'payment.method_name',
                'sortable' => false,
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
            ->select('transactions.uuid', 'transactions.number as serial_number', 'transactions.code_number as voucher_number', 'transactions.date', 'transactions.type', 'transactions.amount', 'transactions.transactionable_id as student_id', 'transactions.is_online', 'transactions.payment_gateway', 'transactions.cancelled_at', 'transactions.rejected_at', 'students.uuid as student_uuid', 'students.roll_number', 'students.batch_id', 'students.contact_id', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'contacts.father_name', 'contacts.contact_number')
            ->withPayment()
            ->where('transactions.head', 'fee_refund')
            ->join('students', function ($join) {
                $join->on('transactions.transactionable_id', '=', 'students.id')->where('transactions.transactionable_type', '=', 'Student')
                    ->join('contacts', function ($join) {
                        $join->on('students.contact_id', '=', 'contacts.id')
                            ->where('contacts.team_id', '=', auth()->user()?->current_team_id);
                    })
                    ->join('batches', function ($join) {
                        $join->on('students.batch_id', '=', 'batches.id')
                            ->leftJoin('courses', function ($join) {
                                $join->on('batches.course_id', '=', 'courses.id');
                            });
                    })
                    ->join('admissions', function ($join) {
                        $join->on('students.admission_id', '=', 'admissions.id');
                    });
            })
            ->where(function ($q) {
                $q->where('is_online', '!=', true)
                    ->orWhere(function ($q) {
                        $q->where('is_online', '=', true)
                            ->whereNotNull('processed_at');
                    });
            })
            ->whereNull('transactions.cancelled_at')
            ->whereNull('rejected_at')
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number,admissions.code_number',
                'App\QueryFilters\LikeMatch:voucher_number,transactions.code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $studentIds = Student::query()
            ->select('students.id')
            ->byPeriod()
            ->filterAccessible()
            ->pluck('id')
            ->all();

        $summary = Transaction::query()
            ->where('transactions.head', 'fee_refund')
            ->join('students', function ($join) {
                $join->on('transactions.transactionable_id', '=', 'students.id')->where('transactions.transactionable_type', '=', 'Student')
                    ->join('contacts', function ($join) {
                        $join->on('students.contact_id', '=', 'contacts.id')
                            ->where('contacts.team_id', '=', auth()->user()?->current_team_id);
                    })
                    ->join('batches', function ($join) {
                        $join->on('students.batch_id', '=', 'batches.id')
                            ->leftJoin('courses', function ($join) {
                                $join->on('batches.course_id', '=', 'courses.id');
                            });
                    })
                    ->join('admissions', function ($join) {
                        $join->on('students.admission_id', '=', 'admissions.id');
                    });
            })
            ->where(function ($q) {
                $q->where('is_online', '!=', true)
                    ->orWhere(function ($q) {
                        $q->where('is_online', '=', true)
                            ->whereNotNull('processed_at');
                    });
            })
            ->whereNull('transactions.cancelled_at')
            ->whereNull('rejected_at')
            ->whereIn('transactions.transactionable_id', $studentIds)
            ->selectRaw('SUM(transactions.amount) as total_fee')
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number,admissions.code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ])
            ->first();

        return FeeRefundListResource::collection($this->filter($request)
            ->whereIn('transactions.transactionable_id', $studentIds)
            ->orderBy('serial_number', $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Fee Refund Report',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'has_footer' => true,
                ],
                'footers' => [
                    ['key' => 'voucherNumber', 'label' => trans('general.total')],
                    ['key' => 'name', 'label' => ''],
                    ['key' => 'fatherName', 'label' => ''],
                    ['key' => 'course', 'label' => ''],
                    ['key' => 'amount', 'label' => \Price::from($summary->total_fee)->formatted],
                    ['key' => 'date', 'label' => ''],
                    ['key' => 'ledger', 'label' => ''],
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
