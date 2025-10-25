<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\TransactionRequest;
use App\Http\Resources\Library\TransactionResource;
use App\Models\Library\Transaction;
use App\Services\Library\TransactionListService;
use App\Services\Library\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:book:issue');
    }

    public function preRequisite(Request $request, TransactionService $service)
    {
        return $service->preRequisite($request);
    }

    public function actionPreRequisite(Request $request, TransactionService $service)
    {
        return $service->actionPreRequisite($request);
    }

    public function index(Request $request, TransactionListService $service)
    {
        return $service->paginate($request);
    }

    public function store(TransactionRequest $request, TransactionService $service)
    {
        $transaction = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('library.transaction.transaction')]),
            'book' => TransactionResource::make($transaction),
        ]);
    }

    public function show(string $transaction, TransactionService $service)
    {
        $transaction = Transaction::findByUuidOrFail($transaction);

        $transaction->load('records.copy.book.author', 'records.copy.condition', 'transactionable');

        return TransactionResource::make($transaction);
    }

    public function update(TransactionRequest $request, string $transaction, TransactionService $service)
    {
        $transaction = Transaction::findByUuidOrFail($transaction);

        $service->update($request, $transaction);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('library.transaction.transaction')]),
        ]);
    }

    public function destroy(string $transaction, TransactionService $service)
    {
        $transaction = Transaction::findByUuidOrFail($transaction);

        $service->deletable($transaction);

        $transaction->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('library.transaction.transaction')]),
        ]);
    }
}
