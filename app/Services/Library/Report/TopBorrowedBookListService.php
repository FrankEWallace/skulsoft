<?php

namespace App\Services\Library\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Library\Report\TopBorrowedBookListResource;
use App\Models\Library\TransactionRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TopBorrowedBookListService extends ListGenerator
{
    protected $allowedSorts = ['count'];

    protected $defaultSort = 'count';

    protected $defaultOrder = 'desc';

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
                'key' => 'title',
                'label' => trans('library.book.props.title'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'author',
                'label' => trans('library.book.props.author'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'publisher',
                'label' => trans('library.book.props.publisher'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'count',
                'label' => trans('general.count'),
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        // if (request()->ajax()) {
        //     $headers[] = $this->actionHeader;
        // }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        return TransactionRecord::query()
            ->join('book_copies', 'book_transaction_records.book_copy_id', '=', 'book_copies.id')
            ->join('books', 'book_copies.book_id', '=', 'books.id')
            ->join('book_transactions', 'book_transaction_records.book_transaction_id', '=', 'book_transactions.id')
            ->leftJoin('options as authors', 'authors.id', '=', 'books.author_id')
            ->leftJoin('options as publishers', 'publishers.id', '=', 'books.publisher_id')
            ->select('books.id', 'books.title', 'authors.name as author_name', 'publishers.name as publisher_name', \DB::raw('COUNT(*) as count'))
            ->groupBy('books.id', 'books.title', 'authors.name', 'publishers.name')
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,issue_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return TopBorrowedBookListResource::collection($this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Top Borrowed Book Report',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
