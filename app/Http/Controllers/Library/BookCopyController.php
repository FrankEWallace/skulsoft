<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\Library\Book;
use App\Services\Library\BookCopyListService;
use App\Services\Library\BookCopyService;
use Illuminate\Http\Request;

class BookCopyController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(BookCopyService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, BookCopyListService $service)
    {
        $this->authorize('viewAny', Book::class);

        return $service->paginate($request);
    }
}
