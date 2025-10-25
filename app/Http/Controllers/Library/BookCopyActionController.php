<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\Library\Book;
use App\Models\Library\BookCopy;
use App\Services\Library\BookCopyActionService;
use Illuminate\Http\Request;

class BookCopyActionController extends Controller
{
    public function preRequisite(Request $request, BookCopy $bookCopy, BookCopyActionService $service)
    {
        $this->authorize('view', Book::class);

        return response()->ok($service->preRequisite($request, $bookCopy));
    }

    public function updateBulkCondition(Request $request, BookCopyActionService $service)
    {
        $this->authorize('bulkUpdate', Book::class);

        $count = $service->updateBulkCondition($request);

        return response()->success([
            'message' => trans('global.updated_with_count', ['attribute' => trans('library.book_copy.book_copy'), 'count' => $count]),
        ]);
    }

    public function updateBulkStatus(Request $request, BookCopyActionService $service)
    {
        $this->authorize('bulkUpdate', Book::class);

        $count = $service->updateBulkStatus($request);

        return response()->success([
            'message' => trans('global.updated_with_count', ['attribute' => trans('library.book_copy.book_copy'), 'count' => $count]),
        ]);
    }

    public function updateBulkLocation(Request $request, BookCopyActionService $service)
    {
        $this->authorize('bulkUpdate', Book::class);

        $count = $service->updateBulkLocation($request);

        return response()->success([
            'message' => trans('global.updated_with_count', ['attribute' => trans('library.book_copy.book_copy'), 'count' => $count]),
        ]);
    }
}
