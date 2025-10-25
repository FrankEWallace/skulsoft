<?php

namespace App\Services\Library;

use App\Models\Library\BookCopy;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\Request;

class BookLabelService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    public function print(Request $request)
    {
        $request->validate([
            'start_number' => ['required', 'integer'],
            'end_number' => ['required', 'integer'],
            'column' => ['required', 'integer'],
            'label_per_page' => ['required', 'integer'],
        ]);

        $bookCopies = BookCopy::query()
            ->whereBetween('number', [$request->start_number, $request->end_number])
            ->get();

        $bookCopies = $bookCopies->map(function ($bookCopy) {
            return [
                'number' => $bookCopy->number,
                'title' => $bookCopy->book->title,
                'author' => $bookCopy->book->author,
                'qr_code' => (new QRCode)->render(
                    $bookCopy->number
                ),
            ];
        });

        $column = $request->query('column') ?? 1;
        $labelPerPage = $request->query('label_per_page') ?? 1;

        return view('print.library.book-label', compact('bookCopies', 'column', 'labelPerPage'));
    }
}
