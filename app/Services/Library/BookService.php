<?php

namespace App\Services\Library;

use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Library\Book;
use App\Models\Library\BookAddition;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function preRequisite(Request $request)
    {
        // $authors = OptionResource::collection(Option::query()
        //     ->byTeam()
        //     ->whereType(OptionType::BOOK_AUTHOR->value)
        //     ->get());

        // $publishers = OptionResource::collection(Option::query()
        //     ->byTeam()
        //     ->whereType(OptionType::BOOK_PUBLISHER->value)
        //     ->get());

        // $languages = OptionResource::collection(Option::query()
        //     ->byTeam()
        //     ->whereType(OptionType::BOOK_LANGUAGE->value)
        //     ->get());

        // $topics = OptionResource::collection(Option::query()
        //     ->byTeam()
        //     ->whereType(OptionType::BOOK_TOPIC->value)
        //     ->get());

        // $categories = OptionResource::collection(Option::query()
        //     ->byTeam()
        //     ->whereType(OptionType::BOOK_CATEGORY->value)
        //     ->get());

        // return compact('authors', 'publishers', 'languages', 'topics', 'categories);

        return [];
    }

    public function create(Request $request): Book
    {
        \DB::beginTransaction();

        $book = Book::forceCreate($this->formatParams($request));

        \DB::commit();

        return $book;
    }

    private function formatParams(Request $request, ?Book $book = null): array
    {
        $formatted = [
            'title' => $request->title,
            'author_id' => $request->author_id,
            'publisher_id' => $request->publisher_id,
            'language_id' => $request->language_id,
            'topic_id' => $request->topic_id,
            'category_id' => $request->category_id,
            'sub_title' => $request->sub_title,
            'subject' => $request->subject,
            'year_published' => $request->year_published,
            'volume' => $request->volume,
            'isbn_number' => $request->isbn_number,
            'call_number' => $request->call_number,
            'edition' => $request->edition,
            'type' => $request->type,
            'page' => (int) $request->page,
            'price' => ! empty($request->price) ? $request->price : 0,
            'summary' => $request->summary,
        ];

        if (! $book) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, Book $book): void
    {
        \DB::beginTransaction();

        $book->forceFill($this->formatParams($request, $book))->save();

        \DB::commit();
    }

    public function deletable(Book $book): void
    {
        $bookAdditionExists = BookAddition::query()
            ->whereBookId($book->id)
            ->exists();

        if ($bookAdditionExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('library.book_addition.book_addition'), 'dependency' => trans('library.book.book')])]);
        }
    }
}
