<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\BookReturnRequest;
use App\Models\Library\Transaction;
use App\Services\Library\TransactionActionService;

class TransactionActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:book:return');
    }

    public function returnBook(BookReturnRequest $request, string $transaction, TransactionActionService $service)
    {
        $transaction = Transaction::findByUuidOrFail($transaction);

        $service->returnBook($request, $transaction);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('library.transaction.transaction')]),
        ]);
    }
}
