<?php

namespace App\Services\Library;

use App\Contracts\ListGenerator;
use App\Http\Resources\Library\BookResource;
use App\Models\Library\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BookListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'title'];

    protected $defaultSort = 'title';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'title',
                'label' => trans('library.book.props.title'),
                'print_label' => 'title',
                'print_sub_label' => 'sub_title',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'copies',
                'label' => trans('library.book_addition.props.copies'),
                'print_label' => 'copies_count',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'availableCopies',
                'label' => trans('library.book.props.available'),
                'print_label' => 'available_copies_count',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'author',
                'label' => trans('library.book.props.author'),
                'print_label' => 'author.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'publisher',
                'label' => trans('library.book.props.publisher'),
                'print_label' => 'publisher.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'topic',
                'label' => trans('library.book.props.topic'),
                'print_label' => 'topic.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'category',
                'label' => trans('library.book.props.category'),
                'print_label' => 'category.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'isbnNumber',
                'label' => trans('library.book.props.isbn_number'),
                'print_label' => 'isbn_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'createdAt',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $authors = Str::toArray($request->query('authors'));
        $publishers = Str::toArray($request->query('publishers'));
        $topics = Str::toArray($request->query('topics'));
        $languages = Str::toArray($request->query('languages'));
        $categories = Str::toArray($request->query('categories'));

        return Book::query()
            ->byTeam()
            ->withCount([
                'copies',
                'availableCopies as available_copies_count',
            ])
            ->with('author', 'publisher', 'topic', 'category')
            ->when($authors, function ($q, $authors) {
                $q->whereHas('author', function ($q) use ($authors) {
                    $q->whereIn('uuid', $authors);
                });
            })
            ->when($publishers, function ($q, $publishers) {
                $q->whereHas('publisher', function ($q) use ($publishers) {
                    $q->whereIn('uuid', $publishers);
                });
            })
            ->when($topics, function ($q, $topics) {
                $q->whereHas('topic', function ($q) use ($topics) {
                    $q->whereIn('uuid', $topics);
                });
            })
            ->when($languages, function ($q, $languages) {
                $q->whereHas('language', function ($q) use ($languages) {
                    $q->whereIn('uuid', $languages);
                });
            })
            ->when($categories, function ($q, $categories) {
                $q->whereHas('category', function ($q) use ($categories) {
                    $q->whereIn('uuid', $categories);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return BookResource::collection($this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->when($request->query('output') == 'export_all_excel', function ($q) {
                return $q->get();
            }, function ($q) {
                return $q->paginate((int) $this->getPageLength(), ['*'], 'current_page');
            }))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Library Book List',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function searchBook(string $query)
    {
        return Book::query()
            ->select('title')
            ->where('title', 'like', '%'.$query.'%')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return $item->title;
            });
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
