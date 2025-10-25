<?php

namespace App\Enums\Academic;

use App\Concerns\HasEnum;

enum BookListType: string
{
    use HasEnum;

    case TEXTBOOK = 'textbook';
    case REFERENCE_BOOK = 'reference_book';
    case NOTEBOOK = 'notebook';

    public static function translation(): string
    {
        return 'academic.book_list.types.';
    }
}
