<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\BookListRequest;
use App\Http\Resources\Academic\BookListResource;
use App\Models\Academic\BookList;
use App\Services\Academic\BookListListService;
use App\Services\Academic\BookListService;
use Illuminate\Http\Request;

class BookListController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:book-list:manage');
    }

    public function preRequisite(BookListService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, BookListListService $service)
    {
        return $service->paginate($request);
    }

    public function store(BookListRequest $request, BookListService $service)
    {
        $bookList = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.book_list.book_list')]),
            'book_list' => BookListResource::make($bookList),
        ]);
    }

    public function show(string $bookList, BookListService $service): BookListResource
    {
        $bookList = BookList::findByUuidOrFail($bookList);

        $bookList->load('course', 'subject');

        return BookListResource::make($bookList);
    }

    public function update(BookListRequest $request, string $bookList, BookListService $service)
    {
        $bookList = BookList::findByUuidOrFail($bookList);

        $service->update($request, $bookList);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.book_list.book_list')]),
        ]);
    }

    public function destroy(string $bookList, BookListService $service)
    {
        $bookList = BookList::findByUuidOrFail($bookList);

        $service->deletable($bookList);

        $bookList->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.book_list.book_list')]),
        ]);
    }
}
