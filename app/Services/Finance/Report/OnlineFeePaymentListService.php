<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Enums\Finance\TransactionStatus;
use App\Http\Resources\Finance\Report\OnlineFeePaymentListResource;
use App\Models\Academic\Period;
use App\Models\Finance\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OnlineFeePaymentListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date', 'code_number', 'name', 'amount'];

    protected $defaultSort = 'created_at';

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
                'print_label' => 'date.formatted',
                'print_sub_label' => 'created_at.formatted',
                'print_additional_label' => 'processed_at.formatted',
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
            [
                'key' => 'user',
                'label' => trans('user.user'),
                'print_label' => 'user.profile.name',
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
            ->select('transactions.uuid', 'transactions.created_at', 'transactions.code_number as voucher_number', 'transactions.date', 'transactions.type', 'transactions.amount', 'transactions.transactionable_id as student_id', 'transactions.is_online', 'transactions.payment_gateway', 'transactions.processed_at', 'transactions.cancelled_at', 'transactions.rejected_at', 'transactions.created_at', 'students.uuid as student_uuid', 'students.roll_number', 'students.batch_id', 'students.contact_id', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'contacts.father_name', 'contacts.contact_number', 'users.name as user_name')
            ->withPayment()
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
            ->leftJoin('users', function ($join) {
                $join->on('transactions.user_id', '=', 'users.id');
            })
            ->where('transactions.is_online', '=', true)
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($request->query('status'), function ($q, $status) {
                if ($status == TransactionStatus::PENDING->value) {
                    $q->where(function ($q) {
                        $q->whereNull('processed_at')->where(function ($q) {
                            $q->whereNull('payment_gateway->status')->orWhere('payment_gateway->status', '!=', 'updated');
                        });
                    });
                } elseif ($status == TransactionStatus::FAILED->value) {
                    $q->where(function ($q) {
                        $q->whereNull('processed_at')->where('payment_gateway->status', '=', 'updated');
                    });
                } elseif ($status == TransactionStatus::SUCCEED->value) {
                    $q->whereNotNull('processed_at');
                } elseif ($status == TransactionStatus::CANCELLED->value) {
                    $q->whereNotNull('transactions.cancelled_at');
                } elseif ($status == TransactionStatus::REJECTED->value) {
                    $q->whereNotNull('transactions.rejected_at');
                }
            })
            ->when($request->query('reference_number'), function ($q, $referenceNumber) {
                $q->where('payment_gateway->reference_number', $referenceNumber);
            })
            ->when($request->query('pg_account'), function ($q, $pgAccount) {
                $q->where('payment_gateway->pg_account', $pgAccount);
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number,admissions.code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
                'App\QueryFilters\DateBetween:start_date,end_date,transactions.date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $periodUuid = $request->query('period');
        $period = $periodUuid ? Period::query()
            ->whereUuid($periodUuid)->first() : null;

        $request->merge([
            'period_id' => $period?->id,
        ]);

        $summary = Transaction::query()
            ->selectRaw('SUM(transactions.amount) as total_fee')
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
            ->leftJoin('users', function ($join) {
                $join->on('transactions.user_id', '=', 'users.id');
            })
            ->where('transactions.is_online', '=', true)
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($request->query('status'), function ($q, $status) {
                if ($status == TransactionStatus::PENDING->value) {
                    $q->where(function ($q) {
                        $q->whereNull('processed_at')->where(function ($q) {
                            $q->whereNull('payment_gateway->status')->orWhere('payment_gateway->status', '!=', 'updated');
                        });
                    });
                } elseif ($status == TransactionStatus::FAILED->value) {
                    $q->where(function ($q) {
                        $q->whereNull('processed_at')->where('payment_gateway->status', '=', 'updated');
                    });
                } elseif ($status == TransactionStatus::SUCCEED->value) {
                    $q->whereNotNull('processed_at');
                } elseif ($status == TransactionStatus::CANCELLED->value) {
                    $q->whereNotNull('transactions.cancelled_at');
                } elseif ($status == TransactionStatus::REJECTED->value) {
                    $q->whereNotNull('transactions.rejected_at');
                }
            })
            ->when($request->query('reference_number'), function ($q, $referenceNumber) {
                $q->where('payment_gateway->reference_number', $referenceNumber);
            })
            ->when($request->query('pg_account'), function ($q, $pgAccount) {
                $q->where('payment_gateway->pg_account', $pgAccount);
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number,admissions.code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
                'App\QueryFilters\DateBetween:start_date,end_date,transactions.date',
            ])
            ->first();

        return OnlineFeePaymentListResource::collection($this->filter($request)
            ->when($period, function ($q, $period) {
                $q->where('transactions.period_id', $period?->id);
            })
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Online Fee Payment Report',
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
                    ['key' => 'user', 'label' => ''],
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
