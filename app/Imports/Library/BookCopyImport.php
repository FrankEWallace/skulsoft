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

class BookCopyImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 1000;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('book-copy');

        $errors = $this->validate($rows);

        $this->checkForErrors('book-copy', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        $books = Book::query()
            ->select('id', 'title')
            ->byTeam()
            ->get()
            ->map(function ($book) {
                return [
                    'id' => Arr::get($book, 'id'),
                    'title' => strtolower(Arr::get($book, 'title')),
                ];
            });

        $conditions = Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_CONDITION->value)
            ->get();

        activity()->disableLogging();

        $rows = $rows->map(function ($row) {
            $additionDate = Arr::get($row, 'date_of_addition');
            $invoiceDate = Arr::get($row, 'date_of_invoice');

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
                ...$row,
                'date_of_addition' => $additionDate,
                'date_of_invoice' => $invoiceDate,
            ];
        })->groupBy('date_of_addition');

        foreach ($rows as $additionDate => $row) {

            $bookAddition = BookAddition::forceCreate([
                'date' => $additionDate,
                'team_id' => auth()->user()?->current_team_id,
                'meta' => [
                    'import_batch_uuid' => $importBatchUuid,
                ],
            ]);

            foreach ($row as $item) {
                $book = $books->firstWhere('title', strtolower(Arr::get($item, 'title')));
                $condition = $conditions->firstWhere('name', Arr::get($item, 'condition'));

                $bookCopy = BookCopy::forceCreate([
                    'book_addition_id' => $bookAddition->id,
                    'number' => Arr::get($item, 'accession_number'),
                    'book_id' => Arr::get($book, 'id'),
                    'condition_id' => Arr::get($condition, 'id'),
                    'vendor' => Arr::get($item, 'vendor'),
                    'invoice_number' => Arr::get($item, 'invoice_number'),
                    'invoice_date' => Arr::get($item, 'date_of_invoice'),
                    'room_number' => Arr::get($item, 'room_number'),
                    'rack_number' => Arr::get($item, 'rack_number'),
                    'shelf_number' => Arr::get($item, 'shelf_number'),
                ]);
            }
        }

        $team = Team::query()
            ->whereId(auth()->user()?->current_team_id)
            ->first();

        $meta = $team->meta ?? [];
        $imports['book_copies'] = Arr::get($meta, 'imports.book_copies', []);
        $imports['book_copies'][] = [
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
        $books = Book::query()
            ->select('id', 'title')
            ->byTeam()
            ->get()
            ->map(function ($book) {
                return strtolower(Arr::get($book, 'title'));
            })
            ->toArray();

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

        $errors = [];

        $newNumbers = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $title = Arr::get($row, 'title');
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
            } elseif (! in_array(strtolower($title), $books)) {
                $errors[] = $this->setError($rowNo, trans('library.book.props.title'), 'invalid');
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

            $newNumbers[] = $number;
        }

        return $errors;
    }
}
