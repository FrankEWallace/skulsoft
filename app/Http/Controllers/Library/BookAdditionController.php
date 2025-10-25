<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\BookAdditionRequest;
use App\Http\Resources\Library\BookAdditionResource;
use App\Models\Library\BookAddition;
use App\Services\Library\BookAdditionListService;
use App\Services\Library\BookAdditionService;
use Illuminate\Http\Request;

class BookAdditionController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, BookAdditionService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, BookAdditionListService $service)
    {
        $this->authorize('viewAny', BookAddition::class);

        return $service->paginate($request);
    }

    public function store(BookAdditionRequest $request, BookAdditionService $service)
    {
        $this->authorize('create', BookAddition::class);

        $bookAddition = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('library.book_addition.book_addition')]),
            'book' => BookAdditionResource::make($bookAddition),
        ]);
    }

    public function show(string $bookAddition, BookAdditionService $service)
    {
        $bookAddition = BookAddition::findByUuidOrFail($bookAddition);

        $this->authorize('view', $bookAddition);

        $bookAddition->load('copies.book', 'copies.condition');

        return BookAdditionResource::make($bookAddition);
    }

    public function update(BookAdditionRequest $request, string $bookAddition, BookAdditionService $service)
    {
        $bookAddition = BookAddition::findByUuidOrFail($bookAddition);

        $this->authorize('update', $bookAddition);

        $service->update($request, $bookAddition);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('library.book_addition.book_addition')]),
        ]);
    }

    public function destroy(string $bookAddition, BookAdditionService $service)
    {
        $bookAddition = BookAddition::findByUuidOrFail($bookAddition);

        $this->authorize('delete', $bookAddition);

        $service->deletable($bookAddition);

        $bookAddition->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('library.book_addition.book_addition')]),
        ]);
    }
}
