<?php

namespace App\Services\Finance;

use App\Contracts\ListGenerator;
use App\Enums\Finance\TransactionStatus;
use App\Http\Resources\Finance\TransactionResource;
use App\Domain\Academic\Models\Period;
use App\Models\Employee\Employee;
use App\Models\Finance\Transaction;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TransactionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date', 'amount'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
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
                'label' => trans('finance.ledger.ledger'),
                'print_label' => 'payment.ledger.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('finance.transaction.props.type'),
                'print_label' => 'type.label',
                'print_sub_label' => 'payment_method.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'category',
                'label' => trans('finance.transaction.category.category_short'),
                'print_label' => 'category.name',
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
                'key' => 'amount',
                'label' => trans('finance.transaction.props.amount'),
                'type' => 'currency',
                'print_label' => 'amount.formatted',
                'sortable' => true,
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
                'key' => 'user',
                'label' => trans('user.user'),
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

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $types = Str::toArray($request->query('types'));
        $paymentMethods = Str::toArray($request->query('payment_methods'));
        $onlinePaymentMethods = Str::toArray($request->query('online_payment_methods'));
        $ledgers = Str::toArray($request->query('ledgers'));
        $secondaryledgers = Str::toArray($request->query('secondary_ledgers'));
        $pendingClearance = $request->query('pendingClearance');

        $head = $request->query('head');

        return Transaction::query()
            ->withRecord()
            ->withPayment()
            ->when($request->period_id, function ($q, $periodId) {
                $q->where('transactions.period_id', $periodId);
            })
            ->with('category', 'transactionable.contact', 'payments.method', 'user')
            ->when($types, function ($q, $types) {
                return $q->whereIn('type', $types);
            })
            ->when($head, function ($q, $head) {
                $q->where('head', $head);
            })
            ->when($paymentMethods, function ($q, $paymentMethods) {
                return $q->whereHas('payments', function ($q) use ($paymentMethods) {
                    $q->whereHas('method', function ($q) use ($paymentMethods) {
                        $q->whereIn('uuid', $paymentMethods);
                    });
                });
            })
            ->when($onlinePaymentMethods, function ($q, $onlinePaymentMethods) {
                return $q->whereHas('payments', function ($q) use ($onlinePaymentMethods) {
                    $q->whereHas('method', function ($q) use ($onlinePaymentMethods) {
                        $q->whereIn('uuid', $onlinePaymentMethods);
                    });
                });
            })
            ->when($ledgers, function ($q, $ledgers) {
                return $q->whereHas('payments', function ($q) use ($ledgers) {
                    $q->whereHas('ledger', function ($q) use ($ledgers) {
                        $q->whereIn('uuid', $ledgers);
                    });
                });
            })
            ->when($pendingClearance, function ($q) {
                return $q->whereHas('payments', function ($q) {
                    $q->whereHas('method', function ($q) {
                        $q->where('config->has_clearing_date', true);
                    })->where(function ($q) {
                        $q->where('details->clearing_date', null)->orWhere('details->clearing_date', '');
                    });
                });
            })
            ->when($secondaryledgers, function ($q, $secondaryledgers) {
                return $q->whereHas('records', function ($q) use ($secondaryledgers) {
                    $q->whereHas('ledger', function ($q) use ($secondaryledgers) {
                        $q->whereIn('uuid', $secondaryledgers);
                    });
                });
            })
            ->when($request->query('pg_account'), function ($q, $pgAccount) {
                $q->where('payment_gateway->pg_account', $pgAccount);
            })
            ->when($request->query('status'), function ($q, $status) {
                if ($status == TransactionStatus::PENDING->value) {
                    $q->where(function ($q) {
                        $q->where('is_online', 1)->whereNull('processed_at')->where(function ($q) {
                            $q->whereNull('payment_gateway->status')->orWhere('payment_gateway->status', '!=', 'updated');
                        });
                    });
                } elseif ($status == TransactionStatus::FAILED->value) {
                    $q->where(function ($q) {
                        $q->where('is_online', 1)->whereNull('processed_at')->where('payment_gateway->status', '=', 'updated');
                    });
                } elseif ($status == TransactionStatus::SUCCEED->value) {
                    $q->where(function ($q) {
                        $q->where('is_online', 0)
                            ->orWhere(function ($q) {
                                $q->where('is_online', 1)->whereNotNull('processed_at');
                            });
                    })->whereNull('transactions.cancelled_at')->whereNull('rejected_at');
                } elseif ($status == TransactionStatus::CANCELLED->value) {
                    $q->whereNotNull('transactions.cancelled_at');
                } elseif ($status == TransactionStatus::REJECTED->value) {
                    $q->whereNotNull('rejected_at');
                }
            })
            ->leftJoin('periods', 'transactions.period_id', '=', 'periods.id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $types = Str::toArray($request->query('types'));
        $categories = Str::toArray($request->query('categories'));
        $paymentMethods = Str::toArray($request->query('payment_methods'));
        $onlinePaymentMethods = Str::toArray($request->query('online_payment_methods'));
        $ledgers = Str::toArray($request->query('ledgers'));
        $secondaryledgers = Str::toArray($request->query('secondary_ledgers'));
        $pendingClearance = $request->query('pendingClearance');

        $head = $request->query('head');

        $periodUuid = $request->query('period');
        $period = $periodUuid ? Period::query()
            ->whereUuid($periodUuid)->first() : null;

        $request->merge([
            'period_id' => $period?->id,
        ]);

        $summary = null;

        $employees = Str::toArray($request->query('employees'));

        $employeeUserIds = [];
        if ($employees) {
            $employeeUserIds = Employee::query()
                ->select('employees.id', 'contacts.id as contact_id', 'contacts.user_id')
                ->join('contacts', 'employees.contact_id', '=', 'contacts.id')
                ->byTeam()
                ->whereIn('employees.uuid', $employees)
                ->pluck('user_id')
                ->toArray();
        }

        if (($request->query('types') == 'payment' || $request->query('types') == 'receipt') && ! empty($request->query('status'))) {
            $summary = Transaction::query()
                ->when($request->period_id, function ($q, $periodId) {
                    $q->where('transactions.period_id', $periodId);
                })
                ->when($types, function ($q, $types) {
                    return $q->whereIn('type', $types);
                })
                ->when($categories, function ($q, $categories) {
                    return $q->whereHas('category', function ($q) use ($categories) {
                        $q->whereIn('uuid', $categories);
                    });
                })
                ->when($head, function ($q, $head) {
                    $q->where('head', $head);
                })
                ->when($paymentMethods, function ($q, $paymentMethods) {
                    return $q->whereHas('payments', function ($q) use ($paymentMethods) {
                        $q->whereHas('method', function ($q) use ($paymentMethods) {
                            $q->whereIn('uuid', $paymentMethods);
                        });
                    });
                })
                ->when($pendingClearance, function ($q) {
                    return $q->whereHas('payments', function ($q) {
                        $q->whereHas('method', function ($q) {
                            $q->where('config->has_clearing_date', true);
                        })->where(function ($q) {
                            $q->where('details->clearing_date', null)->orWhere('details->clearing_date', '');
                        });
                    });
                })
                ->when($onlinePaymentMethods, function ($q, $onlinePaymentMethods) {
                    return $q->whereHas('payments', function ($q) use ($onlinePaymentMethods) {
                        $q->whereHas('method', function ($q) use ($onlinePaymentMethods) {
                            $q->whereIn('uuid', $onlinePaymentMethods);
                        });
                    });
                })
                ->when($ledgers, function ($q, $ledgers) {
                    return $q->whereHas('payments', function ($q) use ($ledgers) {
                        $q->whereHas('ledger', function ($q) use ($ledgers) {
                            $q->whereIn('uuid', $ledgers);
                        });
                    });
                })
                ->when($secondaryledgers, function ($q, $secondaryledgers) {
                    return $q->whereHas('records', function ($q) use ($secondaryledgers) {
                        $q->whereHas('ledger', function ($q) use ($secondaryledgers) {
                            $q->whereIn('uuid', $secondaryledgers);
                        });
                    });
                })
                ->when($employeeUserIds, function ($q, $employeeUserIds) {
                    return $q->whereIn('transactions.user_id', $employeeUserIds);
                })
                ->when($request->query('status'), function ($q, $status) {
                    if ($status == TransactionStatus::PENDING->value) {
                        $q->where(function ($q) {
                            $q->where('is_online', 1)->whereNull('processed_at')->where(function ($q) {
                                $q->whereNull('payment_gateway->status')->orWhere('payment_gateway->status', '!=', 'updated');
                            });
                        });
                    } elseif ($status == TransactionStatus::FAILED->value) {
                        $q->where(function ($q) {
                            $q->where('is_online', 1)->whereNull('processed_at')->where('payment_gateway->status', '=', 'updated');
                        });
                    } elseif ($status == TransactionStatus::SUCCEED->value) {
                        $q->where(function ($q) {
                            $q->where('is_online', 0)
                                ->orWhere(function ($q) {
                                    $q->where('is_online', 1)->whereNotNull('processed_at');
                                });
                        })->whereNull('transactions.cancelled_at')->whereNull('rejected_at');
                    } elseif ($status == TransactionStatus::CANCELLED->value) {
                        $q->whereNotNull('transactions.cancelled_at');
                    } elseif ($status == TransactionStatus::REJECTED->value) {
                        $q->whereNotNull('rejected_at');
                    }
                })
                ->leftJoin('periods', 'transactions.period_id', '=', 'periods.id')
                ->where('periods.team_id', auth()->user()->current_team_id)
                ->filter([
                    'App\QueryFilters\LikeMatch:code_number',
                    'App\QueryFilters\DateBetween:start_date,end_date,date',
                ])
                ->selectRaw('SUM(transactions.amount) as total_amount')
                ->first();
        }

        $request->merge([
            'validate_clearance' => true,
        ]);

        $records = $this->filter($request)
            ->when($employeeUserIds, function ($q, $employeeUserIds) {
                return $q->whereIn('transactions.user_id', $employeeUserIds);
            })
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        $studentIds = $records->filter(function ($record) {
            return $record->transactionable_type == 'Student';
        })->pluck('transactionable_id')->toArray();

        $registrationIds = $records->filter(function ($record) {
            return $record->transactionable_type == 'Registration';
        })->pluck('transactionable_id')->toArray();

        $students = Student::query()
            ->summary()
            ->whereIn('students.id', $studentIds)
            ->get();

        $registrations = Registration::query()
            ->with('contact')
            ->whereIn('registrations.id', $registrationIds)
            ->get();

        $request->merge([
            'students' => $students,
            'registrations' => $registrations,
        ]);

        return TransactionResource::collection($records)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Transaction Report',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'has_footer' => empty($summary) ? false : true,
                ],
                'footers' => [
                    ['key' => 'codeNumber', 'label' => trans('general.total')],
                    ['key' => 'primaryLedger', 'label' => ''],
                    ['key' => 'type', 'label' => ''],
                    ['key' => 'date', 'label' => ''],
                    ['key' => 'amount', 'label' => \Price::from($summary?->total_amount)?->formatted],
                    ['key' => 'secondaryLedger', 'label' => ''],
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
