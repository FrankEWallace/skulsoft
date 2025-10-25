<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\BookRequest;
use App\Http\Resources\Library\BookResource;
use App\Models\Library\Book;
use App\Services\Library\BookListService;
use App\Services\Library\BookService;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, BookService $service)
    {
        return $service->preRequisite($request);
    }

    public function searchBook(Request $request, BookListService $service)
    {
        return response()->json([
            'results' => $service->searchBook($request->get('query')),
        ]);
    }

    public function index(Request $request, BookListService $service)
    {
        $this->authorize('viewAny', Book::class);

        return $service->paginate($request);
    }

    public function store(BookRequest $request, BookService $service)
    {
        $this->authorize('create', Book::class);

        $book = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('library.book.book')]),
            'book' => BookResource::make($book),
        ]);
    }

    public function show(Book $book, BookService $service)
    {
        $this->authorize('view', $book);

        $book->load('author', 'publisher', 'language', 'topic', 'category', 'copies.condition', 'copies.addition');

        return BookResource::make($book);
    }

    public function update(BookRequest $request, Book $book, BookService $service)
    {
        $this->authorize('update', $book);

        $service->update($request, $book);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('library.book.book')]),
        ]);
    }

    public function destroy(Book $book, BookService $service)
    {
        $this->authorize('delete', $book);

        $service->deletable($book);

        $book->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('library.book.book')]),
        ]);
    }
}
