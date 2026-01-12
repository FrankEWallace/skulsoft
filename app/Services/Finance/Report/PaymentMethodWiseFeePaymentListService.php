<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Helpers\CalHelper;
use App\Http\Resources\Finance\Report\PaymentMethodWiseFeePaymentListResource;
use App\Domain\Academic\Models\Period;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentMethodWiseFeePaymentListService extends ListGenerator
{
    protected $allowedSorts = ['date', 'total'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(Collection $paymentMethods): array
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

        foreach ($paymentMethods as $paymentMethod) {
            $headers[] = [
                'key' => Str::camel($paymentMethod->slug),
                'label' => $paymentMethod->name,
                'type' => 'currency',
                'print_label' => 'payment_methods.'.Str::camel($paymentMethod->slug).'.formatted',
                'sortable' => false,
                'visibility' => true,
            ];
        }

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

    public function filter(Request $request, Collection $paymentMethods): Builder
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

        $ledgers = Str::toArray($request->query('ledgers'));

        foreach ($paymentMethods as $paymentMethod) {
            $name = Str::camel($paymentMethod->slug);
            $selectExpressions[] = \DB::raw("SUM(CASE WHEN payment_method_id = $paymentMethod->id THEN transaction_payments.amount ELSE 0 END) as $name");
        }

        return Transaction::query()
            ->select(
                'transactions.date',
                \DB::raw('SUM(transaction_payments.amount) as total'),
                ...$selectExpressions,
            )
            ->when($request->period_id, function ($q, $periodId) {
                $q->byPeriod($periodId);
            })
            ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
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
        $period = $periodUuid ? Period::query()
            ->whereUuid($periodUuid)->first() : null;

        $request->merge([
            'period_id' => $period?->id,
        ]);

        $paymentMethods = PaymentMethod::query()
            ->byTeam()
            ->get();

        $request->merge([
            'payment_method_slugs' => $paymentMethods->pluck('slug')->toArray(),
        ]);

        $startDate = $request->query('start_date', today()->subWeek(1)->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        $selectExpressions = [];

        foreach ($paymentMethods as $paymentMethod) {
            $name = Str::camel($paymentMethod->slug);
            $selectExpressions[] = \DB::raw("SUM(CASE WHEN payment_method_id = $paymentMethod->id THEN transaction_payments.amount ELSE 0 END) as $name");
        }

        $ledgers = Str::toArray($request->query('ledgers'));

        $summary = Transaction::query()
            ->select(
                \DB::raw('SUM(transaction_payments.amount) as total'),
                ...$selectExpressions,
            )
            ->when($request->period_id, function ($q, $periodId) {
                $q->byPeriod($periodId);
            })
            ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
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

        foreach ($request->payment_method_slugs as $paymentMethodSlug) {
            $paymentMethod = Str::camel($paymentMethodSlug);

            array_push($footers, [
                'key' => $paymentMethod,
                'label' => \Price::from($summary->$paymentMethod)->formatted,
            ]);
        }

        array_push($footers, [
            'key' => 'total',
            'label' => \Price::from($summary->total)->formatted,
        ]);

        return PaymentMethodWiseFeePaymentListResource::collection($this->filter($request, $paymentMethods)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders($paymentMethods),
                'meta' => [
                    'filename' => 'Payment Method Wise Fee Payment Report',
                    'sno' => $this->getSno(),
                    'title' => trans('finance.report.payment_method_wise_fee_payment.payment_method_wise_fee_payment'),
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
