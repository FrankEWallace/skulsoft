<?php

namespace App\Services\Inventory;

use App\Contracts\ListGenerator;
use App\Http\Resources\Inventory\VendorStatementResource;
use App\Models\Finance\Ledger;
use App\Models\Finance\Transaction;
use App\Models\Inventory\StockPurchase;
use App\Models\Inventory\StockReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorStatementService extends ListGenerator
{
    protected $allowedSorts = ['date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'asc';

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
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('finance.transaction.props.date'),
                'print_label' => 'date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('finance.transaction.props.type'),
                'print_label' => 'type',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'amount',
                'label' => trans('finance.transaction.props.amount'),
                'print_label' => 'amount.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'balance',
                'label' => trans('inventory.vendor.props.balance'),
                'print_label' => 'balance.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            // $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $ledger = Ledger::query()
            ->where('uuid', $request->query('vendor'))
            ->firstOrFail();

        $startDate = $request->query('start_date', today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', today()->endOfMonth()->toDateString());

        $openingBalance = Ledger::query()
            ->where('id', $ledger->id)
            ->value('opening_balance');

        $previousPurchases = StockPurchase::query()
            ->where('vendor_id', $ledger->id)
            ->where('date', '<', $startDate)
            ->sum('total');

        $previousReturns = StockReturn::query()
            ->where('vendor_id', $ledger->id)
            ->where('date', '<', $startDate)
            ->sum('total');

        $previousPayments = Transaction::query()
            ->join('transaction_records', 'transaction_records.transaction_id', '=', 'transactions.id')
            ->where('transaction_records.ledger_id', $ledger->id)
            ->where('transactions.date', '<', $startDate)
            ->sum('transaction_records.amount');

        $carryForwardBalance = $openingBalance->value + $previousPurchases - $previousReturns - $previousPayments;

        $purchases = StockPurchase::query()
            ->selectRaw('uuid, code_number, date, "Purchase" as type, total as amount')
            ->where('vendor_id', $ledger->id)
            ->whereBetween('date', [$startDate, $endDate]);

        $returns = StockReturn::query()
            ->selectRaw('uuid, code_number, date, "Return" as type, total as amount')
            ->where('vendor_id', $ledger->id)
            ->whereBetween('date', [$startDate, $endDate]);

        $payments = Transaction::query()
            ->join('transaction_records', 'transaction_records.transaction_id', '=', 'transactions.id')
            ->selectRaw('transactions.uuid, code_number, transactions.date, "Payment" as type, transaction_records.amount')
            ->where('transaction_records.ledger_id', $ledger->id)
            ->whereBetween('transactions.date', [$startDate, $endDate]);

        $union = $purchases->unionAll($returns)->unionAll($payments);

        $currentPage = $request->query('current_page', 1);
        $perPage = $this->getPageLength();
        $offset = ($currentPage - 1) * $perPage;

        $combined = \DB::query()
            ->fromSub($union, 'activity')
            ->orderBy('date')
            ->orderByRaw("FIELD(type, 'Purchase', 'Return', 'Payment')")
            ->limit($offset);

        $runningBalance = $carryForwardBalance;

        foreach ($combined->get() as $row) {
            if (strtolower($row->type) === 'purchase') {
                $runningBalance += $row->amount;
            } elseif (strtolower($row->type) === 'return') {
                $runningBalance -= $row->amount;
            } elseif (strtolower($row->type) === 'payment') {
                $runningBalance -= $row->amount;
            }
        }

        $reportData = \DB::query()
            ->fromSub($union, 'activity')
            ->orderBy('date')
            ->orderByRaw("FIELD(type, 'Purchase', 'Return', 'Payment')")
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        $totalPurchases = StockPurchase::query()
            ->where('vendor_id', $ledger->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('total');

        $totalReturns = StockReturn::query()
            ->where('vendor_id', $ledger->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('total');

        $totalPayments = Transaction::query()
            ->join('transaction_records', 'transaction_records.transaction_id', '=', 'transactions.id')
            ->where('transaction_records.ledger_id', $ledger->id)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->sum('transaction_records.amount');

        $finalBalance = $carryForwardBalance + $totalPurchases - $totalReturns - $totalPayments;

        $request->merge([
            'starting_balance' => $carryForwardBalance,
            'final_balance' => $finalBalance,
        ]);

        $currentBalance = $runningBalance;
        $reportData->getCollection()->transform(function ($row) use (&$currentBalance) {
            if (strtolower($row->type) === 'purchase') {
                $currentBalance += $row->amount;
            } elseif (strtolower($row->type) === 'return') {
                $currentBalance -= $row->amount;
            } elseif (strtolower($row->type) === 'payment') {
                $currentBalance -= $row->amount;
            }
            $row->balance = $currentBalance;

            return $row;
        });

        return VendorStatementResource::collection($reportData)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'title' => $ledger->name,
                    'filename' => 'Vendor Statement Report',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'has_footer' => true,
                    'opening_balance' => \Price::from($runningBalance),
                    'final_balance' => \Price::from($finalBalance),
                ],
                'footers' => [
                    ['key' => 'sno', 'label' => ''],
                    ['key' => 'codeNumber', 'label' => trans('general.total')],
                    ['key' => 'date', 'label' => ''],
                    ['key' => 'type', 'label' => ''],
                    ['key' => 'amount', 'label' => ''],
                    ['key' => 'balance', 'label' => \Price::from($finalBalance)->formatted],
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
