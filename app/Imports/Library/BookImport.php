<?php

namespace App\Imports\Library;

use App\Concerns\ItemImport;
use App\Enums\OptionType;
use App\Models\Library\Book;
use App\Models\Option;
use App\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BookImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 1000;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('book');

        $errors = $this->validate($rows);

        $this->checkForErrors('book', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        $authors = Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_AUTHOR->value)
            ->get();

        $publishers = Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_PUBLISHER->value)
            ->get();

        $languages = Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_LANGUAGE->value)
            ->get();

        $topics = Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_TOPIC->value)
            ->get();

        $categories = Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_CATEGORY->value)
            ->get();

        activity()->disableLogging();

        foreach ($rows as $row) {
            $title = Arr::get($row, 'title');
            $authorName = Arr::get($row, 'author');
            $publisherName = Arr::get($row, 'publisher');
            $languageName = Arr::get($row, 'language');
            $topicName = Arr::get($row, 'topic');
            $categoryName = Arr::get($row, 'category');

            $authorId = null;
            $publisherId = null;
            $languageId = null;
            $topicId = null;
            $categoryId = null;

            if ($authorName) {
                $authorId = $authors->filter(function ($author) use ($authorName) {
                    return strtolower($author->name) === strtolower($authorName);
                })->first()?->id;

                if (! $authorId) {
                    $newAuthor = Option::forceCreate([
                        'team_id' => auth()->user()?->current_team_id,
                        'type' => OptionType::BOOK_AUTHOR->value,
                        'name' => Str::title($authorName),
                    ]);

                    $authorId = $newAuthor->id;

                    $authors->push($newAuthor);
                }
            }

            if ($publisherName) {
                $publisherId = $publishers->filter(function ($publisher) use ($publisherName) {
                    return strtolower($publisher->name) === strtolower($publisherName);
                })->first()?->id;

                if (! $publisherId) {
                    $newPublisher = Option::forceCreate([
                        'team_id' => auth()->user()?->current_team_id,
                        'type' => OptionType::BOOK_PUBLISHER->value,
                        'name' => Str::title($publisherName),
                    ]);

                    $publisherId = $newPublisher->id;

                    $publishers->push($newPublisher);
                }
            }

            if ($languageName) {
                $languageId = $languages->filter(function ($language) use ($languageName) {
                    return strtolower($language->name) === strtolower($languageName);
                })->first()?->id;

                if (! $languageId) {
                    $newLanguage = Option::forceCreate([
                        'team_id' => auth()->user()?->current_team_id,
                        'type' => OptionType::BOOK_LANGUAGE->value,
                        'name' => Str::title($languageName),
                    ]);

                    $languageId = $newLanguage->id;

                    $languages->push($newLanguage);
                }
            }

            if ($topicName) {
                $topicId = $topics->filter(function ($topic) use ($topicName) {
                    return strtolower($topic->name) === strtolower($topicName);
                })->first()?->id;

                if (! $topicId) {
                    $newTopic = Option::forceCreate([
                        'team_id' => auth()->user()?->current_team_id,
                        'type' => OptionType::BOOK_TOPIC->value,
                        'name' => Str::title($topicName),
                    ]);

                    $topicId = $newTopic->id;

                    $topics->push($newTopic);
                }
            }

            if ($categoryName) {
                $categoryId = $categories->filter(function ($category) use ($categoryName) {
                    return strtolower($category->name) === strtolower($categoryName);
                })->first()?->id;

                if (! $categoryId) {
                    $newCategory = Option::forceCreate([
                        'team_id' => auth()->user()?->current_team_id,
                        'type' => OptionType::BOOK_CATEGORY->value,
                        'name' => Str::title($categoryName),
                    ]);

                    $categoryId = $newCategory->id;

                    $categories->push($newCategory);
                }
            }

            $book = Book::firstOrCreate([
                'team_id' => auth()->user()?->current_team_id,
                'title' => Str::title($title),
            ], [
                'meta' => [
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);

            $book->author_id = $authorId;
            $book->publisher_id = $publisherId;
            $book->language_id = $languageId;
            $book->topic_id = $topicId;
            $book->category_id = $categoryId;
            $book->subject = Arr::get($row, 'subject');
            $book->price = is_int(Arr::get($row, 'price')) ? Arr::get($row, 'price', 0) : 0;
            $book->page = is_int(Arr::get($row, 'page')) ? Arr::get($row, 'page', 0) : 0;
            $book->sub_title = Str::title(Arr::get($row, 'sub_title'));
            $book->isbn_number = Arr::get($row, 'isbn_number');
            $book->year_published = Arr::get($row, 'year_published');
            $book->volume = Arr::get($row, 'volume');
            $book->call_number = Arr::get($row, 'call_number');
            $book->edition = Arr::get($row, 'edition');
            $book->save();
        }

        $team = Team::query()
            ->whereId(auth()->user()->current_team_id)
            ->first();

        $meta = $team->meta ?? [];
        $imports['book'] = Arr::get($meta, 'imports.book', []);
        $imports['book'][] = [
            'uuid' => $importBatchUuid,
            'total' => count($rows),
            'created_at' => now()->toDateTimeString(),
        ];

        $meta['imports'] = $imports;
        $team->meta = $meta;
        $team->save();

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $existingTitles = Book::query()
            ->byTeam()
            ->pluck('title')
            ->all();

        $errors = [];

        $newTitles = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $title = Arr::get($row, 'title');
            $page = Arr::get($row, 'page');
            $price = Arr::get($row, 'price');

            if (! $title) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'required');
            } elseif (strlen($title) < 2 || strlen($title) > 200) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'min_max', ['min' => 2, 'max' => 200]);
            } elseif (in_array($title, $existingTitles)) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'exists');
            } elseif (in_array($title, $newTitles)) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'duplicate');
            }

            // if ($page) {
            //     if (! is_integer($page)) {
            //         $errors[] = $this->setError($rowNo, trans('library.book.props.page'), 'integer');
            //     }
            // }

            // if ($price) {
            //     if (! is_integer($price)) {
            //         $errors[] = $this->setError($rowNo, trans('library.book.props.price'), 'integer');
            //     }
            // }

            $newTitles[] = $title;
        }

        return $errors;
    }
}
