<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Services\Library\BookWiseTransactionListService;
use App\Services\Library\BookWiseTransactionService;
use Illuminate\Http\Request;

class BookWiseTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:book:issue');
    }

    public function preRequisite(Request $request, BookWiseTransactionService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, BookWiseTransactionListService $service)
    {
        return $service->paginate($request);
    }
}
