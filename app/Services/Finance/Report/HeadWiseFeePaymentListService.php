<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Helpers\CalHelper;
use App\Http\Resources\Finance\Report\HeadWiseFeePaymentListResource;
use App\Models\Academic\Period;
use App\Models\Finance\FeeHead;
use App\Models\Finance\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HeadWiseFeePaymentListService extends ListGenerator
{
    protected $allowedSorts = ['date', 'total'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(Collection $feeHeads): array
    {
        $headers = [
            [
                'key' => 'date',
                'label' => trans('general.date'),
                'print_label' => 'date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        $headers[] = [
            'key' => 'registrationFee',
            'label' => trans('student.registration.fee'),
            'print_label' => 'fee_heads.registration_fee.formatted',
            'sortable' => false,
            'visibility' => true,
        ];

        foreach ($feeHeads as $feeHead) {
            $headers[] = [
                'key' => Str::camel($feeHead->slug),
                'label' => $feeHead->name,
                'type' => 'currency',
                'print_label' => 'fee_heads.'.Str::camel($feeHead->slug).'.formatted',
                'sortable' => false,
                'visibility' => true,
            ];
        }

        $headers[] = [
            'key' => 'transportFee',
            'label' => trans('finance.fee.default_fee_heads.transport_fee'),
            'type' => 'currency',
            'print_label' => 'fee_heads.transport_fee.formatted',
            'sortable' => false,
            'visibility' => true,
        ];

        $headers[] = [
            'key' => 'lateFee',
            'label' => trans('finance.fee.default_fee_heads.late_fee'),
            'type' => 'currency',
            'print_label' => 'fee_heads.late_fee.formatted',
            'sortable' => false,
            'visibility' => true,
        ];

        $headers[] = [
            'key' => 'additionalCharge',
            'label' => trans('finance.fee.default_fee_heads.additional_charge'),
            'type' => 'currency',
            'print_label' => 'fee_heads.additional_charge.formatted',
            'sortable' => false,
            'visibility' => true,
        ];

        $headers[] = [
            'key' => 'additionalDiscount',
            'label' => trans('finance.fee.default_fee_heads.additional_discount'),
            'type' => 'currency',
            'print_label' => 'fee_heads.additional_discount.formatted',
            'sortable' => false,
            'visibility' => true,
        ];

        $headers[] = [
            'key' => 'total',
            'label' => trans('general.total'),
            'type' => 'currency',
            'print_label' => 'total.formatted',
            'sortable' => true,
            'visibility' => true,
        ];

        // if (request()->ajax()) {
        //     $headers[] = $this->actionHeader;
        // }

        return $headers;
    }

    public function filter(Request $request, Collection $feeHeads): Builder
    {
        $startDate = $request->query('start_date', today()->subWeek(1)->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        if (! CalHelper::validateDate($startDate)) {
            throw ValidationException::withMessages(['message' => trans('validation.date', ['attribute' => trans('general.start_date')])]);
        }

        if (! CalHelper::validateDate($endDate)) {
            throw ValidationException::withMessages(['message' => trans('validation.date', ['attribute' => trans('general.end_date')])]);
        }

        if ($startDate > $endDate) {
            $startDate = $request->query('end_date');
            $endDate = $request->query('start_date');
        }

        $selectExpressions = [];

        foreach ($feeHeads as $feeHead) {
            $name = Str::camel($feeHead->slug);
            $selectExpressions[] = \DB::raw("SUM(CASE WHEN fee_head_id = $feeHead->id THEN student_fee_payments.amount ELSE 0 END) as $name");
        }

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN default_fee_head = 'transport_fee' THEN student_fee_payments.amount ELSE 0 END) as transportFee");

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN default_fee_head = 'late_fee' THEN student_fee_payments.amount ELSE 0 END) as lateFee");

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN default_fee_head = 'additional_charge' THEN student_fee_payments.amount ELSE 0 END) as additionalCharge");

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN default_fee_head = 'additional_discount' THEN student_fee_payments.amount ELSE 0 END) as additionalDiscount");

        $selectExpressions[] = \DB::raw(
            "SUM(CASE WHEN transactions.head = 'registration_fee' THEN transactions.amount ELSE 0 END) as registrationFee"
        );

        $ledgers = Str::toArray($request->query('ledgers'));
        $paymentMethods = Str::toArray($request->query('payment_methods'));

        return Transaction::query()
            ->select(
                'transactions.date',
                \DB::raw(
                    'SUM(CASE WHEN default_fee_head IS NULL THEN student_fee_payments.amount ELSE 0 END) +
                     SUM(CASE WHEN fee_head_id IS NULL AND default_fee_head != "additional_discount" THEN student_fee_payments.amount ELSE 0 END) -
                     SUM(CASE WHEN fee_head_id IS NULL AND default_fee_head = "additional_discount" THEN student_fee_payments.amount ELSE 0 END) +
                     SUM(CASE WHEN transactions.head = "registration_fee" THEN transactions.amount ELSE 0 END) as total'
                ),
                ...$selectExpressions,
            )
            ->byPeriod($request->period_id)
            ->leftJoin('student_fee_payments', 'transactions.id', '=', 'student_fee_payments.transaction_id')
            ->leftJoin('periods', 'transactions.period_id', '=', 'periods.id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->whereIn('transactions.head', ['student_fee', 'registration_fee'])
            ->when($ledgers, function ($q, $ledgers) {
                $q->whereHas('payments', function ($q) use ($ledgers) {
                    $q->whereHas('ledger', function ($q) use ($ledgers) {
                        $q->whereIn('uuid', $ledgers);
                    });
                });
            })
            ->when($paymentMethods, function ($q, $paymentMethods) {
                $q->whereHas('payments', function ($q) use ($paymentMethods) {
                    $q->whereHas('method', function ($q) use ($paymentMethods) {
                        $q->whereIn('uuid', $paymentMethods);
                    });
                });
            })
            ->whereNull('transactions.cancelled_at')
            ->whereNull('rejected_at')
            ->where(function ($q) {
                $q->where('is_online', '!=', true)
                    ->orWhere(function ($q) {
                        $q->where('is_online', '=', true)
                            ->whereNotNull('processed_at');
                    });
            })
            ->when($request->query('pg_account'), function ($q, $pgAccount) {
                $q->where('payment_gateway->pg_account', $pgAccount);
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('date');
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $periodUuid = $request->query('period');
        $period = Period::query()
            ->when($periodUuid, function ($q, $periodUuid) {
                $q->whereUuid($periodUuid);
            }, function ($q) {
                $q->whereId(auth()->user()->current_period_id);
            })
            ->first();

        $request->merge([
            'period_id' => $period?->id,
        ]);

        $feeHeads = FeeHead::query()
            ->byPeriod($request->period_id)
            ->get();

        $request->merge([
            'fee_head_slugs' => $feeHeads->pluck('slug')->toArray(),
        ]);

        $startDate = $request->query('start_date', today()->subWeek(1)->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        $selectExpressions = [];

        foreach ($feeHeads as $feeHead) {
            $name = Str::camel($feeHead->slug);
            $selectExpressions[] = \DB::raw("SUM(CASE WHEN fee_head_id = $feeHead->id THEN student_fee_payments.amount ELSE 0 END) as $name");
        }

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN default_fee_head = 'transport_fee' THEN student_fee_payments.amount ELSE 0 END) as transportFee");

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN default_fee_head = 'late_fee' THEN student_fee_payments.amount ELSE 0 END) as lateFee");

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN default_fee_head = 'additional_charge' THEN student_fee_payments.amount ELSE 0 END) as additionalCharge");

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN default_fee_head = 'additional_discount' THEN student_fee_payments.amount ELSE 0 END) as additionalDiscount");

        $selectExpressions[] = \DB::raw("SUM(CASE WHEN transactions.head = 'registration_fee' THEN transactions.amount ELSE 0 END) as registrationFee");

        $ledgers = Str::toArray($request->query('ledgers'));
        $paymentMethods = Str::toArray($request->query('payment_methods'));

        $summary = Transaction::query()
            ->select(
                \DB::raw(
                    'SUM(CASE WHEN default_fee_head IS NULL THEN student_fee_payments.amount ELSE 0 END) +
                     SUM(CASE WHEN fee_head_id IS NULL AND default_fee_head != "additional_discount" THEN student_fee_payments.amount ELSE 0 END) -
                     SUM(CASE WHEN fee_head_id IS NULL AND default_fee_head = "additional_discount" THEN student_fee_payments.amount ELSE 0 END) +
                     SUM(CASE WHEN transactions.head = "registration_fee" THEN transactions.amount ELSE 0 END) as total'
                ),
                ...$selectExpressions,
            )
            ->byPeriod($request->period_id)
            ->leftJoin('student_fee_payments', 'transactions.id', '=', 'student_fee_payments.transaction_id')
            ->leftJoin('periods', 'transactions.period_id', '=', 'periods.id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->whereIn('transactions.head', ['student_fee', 'registration_fee'])
            ->when($ledgers, function ($q, $ledgers) {
                $q->whereHas('payments', function ($q) use ($ledgers) {
                    $q->whereHas('ledger', function ($q) use ($ledgers) {
                        $q->whereIn('uuid', $ledgers);
                    });
                });
            })
            ->when($paymentMethods, function ($q, $paymentMethods) {
                $q->whereHas('payments', function ($q) use ($paymentMethods) {
                    $q->whereHas('method', function ($q) use ($paymentMethods) {
                        $q->whereIn('uuid', $paymentMethods);
                    });
                });
            })
            ->whereNull('transactions.cancelled_at')
            ->whereNull('rejected_at')
            ->where(function ($q) {
                $q->where('is_online', '!=', true)
                    ->orWhere(function ($q) {
                        $q->where('is_online', '=', true)
                            ->whereNotNull('processed_at');
                    });
            })
            ->when($request->query('pg_account'), function ($q, $pgAccount) {
                $q->where('payment_gateway->pg_account', $pgAccount);
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->first();

        $footers = [];

        array_push($footers, [
            'key' => 'date',
            'label' => trans('general.total'),
        ]);

        array_push($footers, [
            'key' => 'registrationFee',
            'label' => \Price::from($summary->registrationFee)->formatted,
        ]);

        foreach ($request->fee_head_slugs as $feeHeadSlug) {
            $feeHead = Str::camel($feeHeadSlug);

            array_push($footers, [
                'key' => $feeHead,
                'label' => \Price::from($summary->$feeHead)->formatted,
            ]);
        }

        array_push($footers, [
            'key' => 'transportFee',
            'label' => \Price::from($summary->transportFee)->formatted,
        ]);

        array_push($footers, [
            'key' => 'lateFee',
            'label' => \Price::from($summary->lateFee)->formatted,
        ]);

        array_push($footers, [
            'key' => 'additionalCharge',
            'label' => \Price::from($summary->additionalCharge)->formatted,
        ]);

        array_push($footers, [
            'key' => 'additionalDiscount',
            'label' => \Price::from($summary->additionalDiscount)->formatted,
        ]);

        array_push($footers, [
            'key' => 'total',
            'label' => \Price::from($summary->total)->formatted,
        ]);

        return HeadWiseFeePaymentListResource::collection($this->filter($request, $feeHeads)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders($feeHeads),
                'meta' => [
                    'filename' => 'Head Wise Fee Payment Report',
                    'sno' => $this->getSno(),
                    'title' => trans('finance.report.head_wise_fee_payment.head_wise_fee_payment'),
                    'layout' => [
                        'type' => 'full-page',
                    ],
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'has_footer' => true,
                ],
                'footers' => $footers,
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
