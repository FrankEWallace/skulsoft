<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Enums\Finance\TransactionType;
use App\Helpers\CalHelper;
use App\Http\Resources\Finance\Report\DayBookListResource;
use App\Models\Employee\Employee;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Student\FeePayment;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class DayBookListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date', 'voucher_number'];

    protected $defaultSort = 'created_at';

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
                'key' => 'codeNumber',
                'label' => trans('finance.transaction.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'reference_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'primaryLedger',
                'label' => trans('finance.ledger.primary_ledger'),
                'print_label' => 'payment.ledger.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'secondaryLedger',
                'label' => trans('finance.ledger.secondary_ledger'),
                'print_label' => 'record.ledger.name',
                'print_sub_label' => 'transactionable.name',
                'sortable' => false,
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
                'key' => 'payment',
                'label' => trans('finance.transaction.types.payment'),
                'type' => 'currency',
                'print_label' => 'payment.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'receipt',
                'label' => trans('finance.transaction.types.receipt'),
                'type' => 'currency',
                'print_label' => 'receipt.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'user',
                'label' => trans('user.user'),
                'type' => 'user',
                'print_label' => 'user.profile.name',
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

        // if (request()->ajax()) {
        //     $headers[] = $this->actionHeader;
        // }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $date = $request->query('date', today()->toDateString());

        if (! CalHelper::validateDate($date)) {
            $date = today()->toDateString();
        }

        return Transaction::query()
            ->select('transactions.*')
            ->leftJoin('periods', 'periods.id', '=', 'transactions.period_id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->withRecord()
            ->withPayment()
            ->with('transactionable.contact', 'user')
            ->where('transactions.date', $date)
            ->whereIn('transactions.type', [TransactionType::PAYMENT, TransactionType::RECEIPT])
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereIsOnline(false)->whereNull('cancelled_at')->whereNull('rejected_at');
                })
                    ->orWhere(function ($q) {
                        $q->whereIsOnline(true)->whereNotNull('processed_at');
                    });
            })
            ->filter([
                // 'App\QueryFilters\ExactMatch:date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $date = $request->query('date', today()->toDateString());

        if (! CalHelper::validateDate($date)) {
            $date = today()->toDateString();
        }

        $employeeUserId = Employee::query()
            ->select('employees.id', 'contacts.id as contact_id', 'contacts.user_id')
            ->join('contacts', 'employees.contact_id', '=', 'contacts.id')
            ->byTeam()
            ->where('employees.uuid', $request->query('employee'))
            ->first()
            ?->user_id;

        $paymentMethods = PaymentMethod::query()
            ->byTeam()
            ->get();

        $paymentMethodSlugs = $paymentMethods->pluck('slug')->toArray();

        $selectExpressions = [];

        foreach ($paymentMethods as $paymentMethod) {
            $name = Str::camel($paymentMethod->slug);
            $selectExpressions[] = \DB::raw("SUM(CASE WHEN payment_method_id = $paymentMethod->id THEN transaction_payments.amount ELSE 0 END) as $name");
        }

        $paymentMethodSummary = Transaction::query()
            ->select(
                \DB::raw('SUM(transaction_payments.amount) as total'),
                ...$selectExpressions,
            )
            ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->leftJoin('periods', 'periods.id', '=', 'transactions.period_id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->where('transactions.date', $date)
            ->whereIn('transactions.type', [TransactionType::PAYMENT, TransactionType::RECEIPT])
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereIsOnline(false)->whereNull('cancelled_at')->whereNull('rejected_at');
                })
                    ->orWhere(function ($q) {
                        $q->whereIsOnline(true)->whereNotNull('processed_at');
                    });
            })
            ->when($employeeUserId, function ($q) use ($employeeUserId) {
                return $q->where('transactions.user_id', $employeeUserId);
            })
            ->filter([
                // 'App\QueryFilters\ExactMatch:date',
            ])
            ->first();

        $summary = Transaction::query()
            ->selectRaw('SUM(transactions.amount) as total')
            ->selectRaw('SUM(CASE WHEN transactions.type = ? THEN transactions.amount ELSE 0 END) as payment', [TransactionType::PAYMENT])
            ->selectRaw('SUM(CASE WHEN transactions.type = ? THEN transactions.amount ELSE 0 END) as receipt', [TransactionType::RECEIPT])
            ->where('transactions.date', $date)
            ->whereIn('transactions.type', [TransactionType::PAYMENT, TransactionType::RECEIPT])
            ->leftJoin('periods', 'periods.id', '=', 'transactions.period_id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereIsOnline(false)->whereNull('cancelled_at')->whereNull('rejected_at');
                })
                    ->orWhere(function ($q) {
                        $q->whereIsOnline(true)->whereNotNull('processed_at');
                    });
            })
            ->when($employeeUserId, function ($q) use ($employeeUserId) {
                return $q->where('transactions.user_id', $employeeUserId);
            })
            ->filter([
                // 'App\QueryFilters\ExactMatch:date',
            ])
            ->first();

        $transactions = Transaction::query()
            ->select('transactions.id', 'transactions.code_number', 'transactions.date', 'transactions.amount', 'transactions.head', 'transaction_payments.payment_method_id', 'transaction_payments.amount as payment_amount')
            ->join('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->leftJoin('periods', 'periods.id', '=', 'transactions.period_id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->where('transactions.date', $date)
            ->whereIn('transactions.type', [TransactionType::PAYMENT, TransactionType::RECEIPT])
            ->where(function ($q) {
                $q->whereNull('transactions.head')->orWhereIn('transactions.head', ['student_fee', 'registration_fee']);
            })
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('transactions.cancelled_at')->whereNull('transactions.rejected_at');
                })->orWhere(function ($q) {
                    $q->whereIsOnline(true)->whereNotNull('transactions.rejected_at');
                });
            })
            ->when($employeeUserId, function ($q) use ($employeeUserId) {
                return $q->where('transactions.user_id', $employeeUserId);
            })
            ->get();

        $studentFeePayments = FeePayment::query()
            ->with('head')
            ->whereIn('transaction_id', $transactions->pluck('id')->toArray())
            ->get();

        $feeHeads = $studentFeePayments->filter(function ($studentFeePayment) {
            return ! empty($studentFeePayment->fee_head_id);
        })->pluck('head.name')->unique()->toArray();

        array_unshift($feeHeads, trans('student.registration.fee'));
        array_push($feeHeads, trans('finance.fee.default_fee_heads.late_fee'));
        array_push($feeHeads, trans('finance.fee.default_fee_heads.transport_fee'));
        array_push($feeHeads, trans('finance.fee.default_fee_heads.additional_charge'));
        array_push($feeHeads, trans('finance.fee.default_fee_heads.additional_discount'));
        array_push($feeHeads, trans('general.other'));

        $defaultFeeHeads = [];

        foreach ($feeHeads as $feeHead) {
            $defaultFeeHeads[] = [
                'key' => Str::camel($feeHead),
                'name' => $feeHead,
                'amount' => 0,
            ];
        }

        $data = [];
        foreach ($paymentMethods as $paymentMethod) {
            $feeHead = Str::camel($paymentMethod->slug);
            $data[] = [
                'uuid' => $paymentMethod->uuid,
                'name' => $paymentMethod->name,
                'amount' => \Price::from($paymentMethodSummary->$feeHead),
                'heads' => $defaultFeeHeads,
            ];
        }

        $feeHeadData = [];
        foreach ($paymentMethods as $paymentMethod) {
            $heads = [];
            $filteredTransactions = $transactions->where('payment_method_id', $paymentMethod->id);

            $filteredStudentFeePayments = $studentFeePayments->whereIn('transaction_id', $filteredTransactions->pluck('id')->toArray());

            foreach ($filteredTransactions->where('head', 'registration_fee') as $registrationTransaction) {
                $heads[] = [
                    'name' => trans('student.registration.fee'),
                    'amount' => $registrationTransaction->amount,
                ];
            }

            foreach ($filteredStudentFeePayments as $studentFeePayment) {
                $feeHead = $studentFeePayment->fee_head_id ? $studentFeePayment->head->name : trans('finance.fee.default_fee_heads.'.$studentFeePayment->default_fee_head->value);

                $heads[] = [
                    'name' => $feeHead,
                    'amount' => $studentFeePayment->amount,
                ];
            }

            $otherAmount = $filteredTransactions->whereNull('head')->sum('amount.value');

            $heads[] = [
                'name' => trans('general.other'),
                'amount' => \Price::from($otherAmount),
            ];

            $feeHeadData[] = [
                'uuid' => $paymentMethod->uuid,
                'name' => $paymentMethod->name,
                'heads' => $heads,
            ];
        }

        $data = collect($data)->map(function ($item) use ($feeHeadData) {
            $paymentMethodData = collect($feeHeadData)->where('uuid', $item['uuid'])->first();

            $newFeeHeads = [];
            if ($paymentMethodData) {
                foreach ($item['heads'] as $head) {
                    $paymentMethodHead = collect($paymentMethodData['heads'])->where('name', $head['name'])->sum('amount.value');

                    $head['amount'] = \Price::from($paymentMethodHead);

                    $newFeeHeads[] = $head;
                }
            }

            $item['heads'] = $newFeeHeads;

            return $item;
        })->toArray();

        $headTotals = [];
        foreach ($data as $paymentMethod) {
            foreach ($paymentMethod['heads'] as $head) {
                if (! isset($headTotals[$head['key']])) {
                    $headTotals[$head['key']] = [
                        'key' => $head['key'],
                        'name' => $head['name'],
                        'amount' => 0,
                    ];
                }
                $headTotals[$head['key']]['amount'] += $head['amount']?->value ?? 0;
            }
        }
        array_push($headTotals, [
            'key' => 'grand_total',
            'amount' => collect($headTotals)->sum(function ($item) {
                return $item['key'] == 'additionalDiscount' ? $item['amount'] * -1 : $item['amount'];
            }),
        ]);

        $headTotals = collect(array_values($headTotals))->map(function ($item) {
            $item['amount'] = \Price::from($item['amount'] ?? 0);

            return $item;
        })->toArray();

        $summaryHeaders = [
            [
                'label' => trans('finance.payment_method.payment_method'),
                'key' => 'paymentMethod',
                'visibility' => true,
            ],
        ];

        foreach ($feeHeads as $feeHead) {
            $summaryHeaders[] = [
                'label' => $feeHead,
                'key' => Str::camel($feeHead),
                'visibility' => true,
            ];
        }

        array_push($summaryHeaders, [
            'label' => trans('general.total'),
            'key' => 'total',
            'visibility' => true,
        ]);

        $records = $this->filter($request)
            ->when($employeeUserId, function ($q) use ($employeeUserId) {
                return $q->where('transactions.user_id', $employeeUserId);
            })
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        $studentIds = $records->filter(function ($record) {
            return $record->transactionable_type == 'Student';
        })->pluck('transactionable_id')->toArray();

        $students = Student::query()
            ->summary()
            ->whereIn('students.id', $studentIds)
            ->get();

        $request->merge([
            'students' => $students,
        ]);

        return DayBookListResource::collection($records)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Day Book Report',
                    'custom_layout' => 'finance.report.day-book',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'has_footer' => true,
                    'payment_method_heads' => $summaryHeaders,
                    'payment_method_summary' => $data,
                    'payment_method_footer' => $headTotals,
                ],
                'footers' => [
                    ['key' => 'sno', 'label' => trans('general.total')],
                    ['key' => 'codeNumber', 'label' => ''],
                    ['key' => 'date', 'label' => ''],
                    ['key' => 'payment', 'label' => \Price::from($summary->payment)->formatted],
                    ['key' => 'receipt', 'label' => \Price::from($summary->receipt)->formatted],
                    ['key' => 'user', 'label' => ''],
                    ['key' => 'createdAt', 'label' => ''],
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
