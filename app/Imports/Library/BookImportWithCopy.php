<?php

namespace App\Imports\Library;

use App\Concerns\ItemImport;
use App\Enums\OptionType;
use App\Helpers\CalHelper;
use App\Models\Library\Book;
use App\Models\Library\BookAddition;
use App\Models\Library\BookCopy;
use App\Models\Option;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BookImportWithCopy implements ToCollection, WithHeadingRow
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

        $conditions = Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_CONDITION->value)
            ->get();

        activity()->disableLogging();

        // $rows = $rows->unique('number')->values();

        $rows = $rows->groupBy('title');

        foreach ($rows as $title => $row) {
            $book = Book::firstOrCreate([
                'team_id' => auth()->user()?->current_team_id,
                'title' => Str::title($title),
            ], [
                'meta' => [
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);

            $firstRow = $row->first();

            $authorName = Arr::get($firstRow, 'author');
            $publisherName = Arr::get($firstRow, 'publisher');
            $languageName = Arr::get($firstRow, 'language');
            $topicName = Arr::get($firstRow, 'topic');
            $categoryName = Arr::get($firstRow, 'category');

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

            $book->author_id = $authorId;
            $book->publisher_id = $publisherId;
            $book->language_id = $languageId;
            $book->topic_id = $topicId;
            $book->category_id = $categoryId;
            $book->subject = Str::title(Arr::get($firstRow, 'subject'));
            $book->price = is_int(Arr::get($firstRow, 'price')) ? Arr::get($firstRow, 'price', 0) : 0;
            $book->page = is_int(Arr::get($firstRow, 'page')) ? Arr::get($firstRow, 'page', 0) : 0;
            $book->sub_title = Str::title(Arr::get($firstRow, 'sub_title'));
            $book->isbn_number = Arr::get($firstRow, 'isbn_number');
            $book->year_published = Arr::get($firstRow, 'year_published');
            $book->volume = Arr::get($firstRow, 'volume');
            $book->call_number = Arr::get($firstRow, 'call_number');
            $book->edition = Arr::get($firstRow, 'edition');
            $book->save();

            $copyRows = $row->map(function ($item) {
                $additionDate = Arr::get($item, 'date_of_addition');
                $invoiceDate = Arr::get($item, 'date_of_invoice');

                if (is_int($additionDate)) {
                    $additionDate = Date::excelToDateTimeObject($additionDate)->format('Y-m-d');
                } else {
                    $additionDate = Carbon::parse($additionDate)->toDateString();
                }

                if (empty($invoiceDate)) {
                    $invoiceDate = null;
                } elseif (is_int($invoiceDate)) {
                    $invoiceDate = Date::excelToDateTimeObject($invoiceDate)->format('Y-m-d');
                } else {
                    $invoiceDate = Carbon::parse($invoiceDate)->toDateString();
                }

                return [
                    ...$item,
                    'date_of_addition' => $additionDate,
                    'date_of_invoice' => $invoiceDate,
                ];
            })->groupBy('date_of_addition');

            foreach ($copyRows as $additionDate => $itemRow) {

                $bookAddition = BookAddition::forceCreate([
                    'date' => $additionDate,
                    'team_id' => auth()->user()?->current_team_id,
                    'meta' => [
                        'import_batch_uuid' => $importBatchUuid,
                    ],
                ]);

                foreach ($itemRow as $itemDetail) {
                    $condition = $conditions->firstWhere('name', Arr::get($itemDetail, 'condition'));

                    $bookCopy = BookCopy::forceCreate([
                        'book_addition_id' => $bookAddition->id,
                        'number' => Arr::get($itemDetail, 'accession_number'),
                        'book_id' => $book->id,
                        'condition_id' => Arr::get($condition, 'id'),
                        'vendor' => Arr::get($itemDetail, 'vendor'),
                        'invoice_number' => Arr::get($itemDetail, 'invoice_number'),
                        'invoice_date' => Arr::get($itemDetail, 'date_of_invoice'),
                        'room_number' => Arr::get($itemDetail, 'room_number'),
                        'rack_number' => Arr::get($itemDetail, 'rack_number'),
                        'shelf_number' => Arr::get($itemDetail, 'shelf_number'),
                    ]);
                }
            }
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
        // $rows = $rows->unique('number')->values();

        $conditions = Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_CONDITION->value)
            ->get();

        $existingNumbers = BookCopy::query()
            ->whereHas('book', function ($query) {
                $query->byTeam();
            })
            ->pluck('number')
            ->all();

        $existingTitles = Book::query()
            ->byTeam()
            ->pluck('title')
            ->all();

        $existingNumbers = BookCopy::query()
            ->whereHas('book', function ($query) {
                $query->byTeam();
            })
            ->pluck('number')
            ->all();

        $errors = [];

        $newTitles = [];
        $newNumbers = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $title = Arr::get($row, 'title');
            $number = Arr::get($row, 'number');
            $page = Arr::get($row, 'page');
            $price = Arr::get($row, 'price');
            $number = Arr::get($row, 'accession_number');
            $condition = Arr::get($row, 'condition');
            $additionDate = Arr::get($row, 'date_of_addition');
            $vendor = Arr::get($row, 'vendor');
            $invoiceNumber = Arr::get($row, 'invoice_number');
            $invoiceDate = Arr::get($row, 'date_of_invoice');
            $roomNumber = Arr::get($row, 'room_number');
            $rackNumber = Arr::get($row, 'rack_number');
            $shelfNumber = Arr::get($row, 'shelf_number');

            if (! $title) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'required');
            } elseif (strlen($title) < 2 || strlen($title) > 200) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'min_max', ['min' => 2, 'max' => 200]);
                // } elseif (in_array($title, $existingTitles)) {
                //     $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'exists');
                // } elseif (in_array($title, $newTitles)) {
                //     $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'duplicate');
            }

            if (! $number) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.number'), 'required');
            } elseif (! is_int($number)) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.number'), 'required');
            } elseif (in_array($number, $existingNumbers)) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.number'), 'exists');
            } elseif (in_array($number, $newNumbers)) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.number'), 'duplicate');
            }

            if (! $additionDate) {
                $errors[] = $this->setError($rowNo, trans('library.book_addition.props.date'), 'required');
            }

            if (is_int($additionDate)) {
                $additionDate = Date::excelToDateTimeObject($additionDate)->format('Y-m-d');
            }

            if ($additionDate && ! CalHelper::validateDate($additionDate)) {
                $errors[] = $this->setError($rowNo, trans('library.book_addition.props.date'), 'invalid');
            }

            if ($invoiceDate && is_int($invoiceDate)) {
                $invoiceDate = Date::excelToDateTimeObject($invoiceDate)->format('Y-m-d');
            }

            if ($invoiceDate && ! CalHelper::validateDate($invoiceDate)) {
                $errors[] = $this->setError($rowNo, trans('library.book_addition.props.invoice_date'), 'invalid');
            }

            if ($condition && ! $conditions->firstWhere('name', $condition)) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.condition'), 'invalid');
            }

            if ($roomNumber && ! is_int($roomNumber)) {
                $errors[] = $this->setError($rowNo, trans('library.book_addition.props.room_number'), 'integer');
            }

            if ($rackNumber && ! is_int($rackNumber)) {
                $errors[] = $this->setError($rowNo, trans('library.book_addition.props.rack_number'), 'integer');
            }

            if ($shelfNumber && ! is_int($shelfNumber)) {
                $errors[] = $this->setError($rowNo, trans('library.book_addition.props.shelf_number'), 'integer');
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
            $newNumbers[] = $number;
        }

        return $errors;
    }
}
