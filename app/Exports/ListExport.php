<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ListExport implements FromArray, ShouldAutoSize
{
    protected $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function array(): array
    {
        return $this->items;
    }
}
