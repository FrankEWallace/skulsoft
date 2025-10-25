<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TransactionRequest;
use App\Http\Resources\Finance\TransactionResource;
use App\Models\Finance\Transaction;
use App\Services\Finance\TransactionListService;
use App\Services\Finance\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(TransactionService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, TransactionListService $service)
    {
        $this->authorize('viewAny', Transaction::class);

        return $service->paginate($request);
    }

    public function store(TransactionRequest $request, TransactionService $service)
    {
        $this->authorize('create', Transaction::class);

        $transaction = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.transaction.transaction')]),
            'transaction' => TransactionResource::make($transaction),
        ]);
    }

    public function show(Transaction $transaction, TransactionService $service)
    {
        $transaction->load('category', 'payments.ledger', 'payments.method', 'records.ledger', 'transactionable.contact', 'media');

        $this->authorize('view', $transaction);

        return TransactionResource::make($transaction);
    }

    public function update(TransactionRequest $request, Transaction $transaction, TransactionService $service)
    {
        $this->authorize('update', $transaction);

        $service->update($transaction, $request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.transaction.transaction')]),
        ]);
    }

    public function destroy(Transaction $transaction, TransactionService $service)
    {
        $this->authorize('delete', $transaction);

        $service->deletable($transaction);

        $service->delete($transaction);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.transaction.transaction')]),
        ]);
    }

    public function downloadMedia(Transaction $transaction, string $uuid, TransactionService $service)
    {
        $this->authorize('view', $transaction);

        return $transaction->downloadMedia($uuid);
    }

    public function export(Request $request, string $transaction, TransactionService $service)
    {
        $transaction = Transaction::query()
            ->withRecord()
            ->findByUuidOrFail($transaction);

        $transaction->load('category', 'payments.ledger', 'payments.method', 'transactionable.contact', 'user');

        $transaction = json_decode(TransactionResource::make($transaction)->toJson(), true);

        return view()->first([config('config.print.custom_path').'finance.transaction', 'print.finance.transaction'], compact('transaction'));
    }
}
