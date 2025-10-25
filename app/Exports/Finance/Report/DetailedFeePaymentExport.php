<?php

namespace App\Exports\Finance\Report;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DetailedFeePaymentExport implements FromArray, ShouldAutoSize, WithStyles
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

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestDataColumn();

        $sheet->mergeCells('A1:'.$highestColumn.'1');

        return [
            'A1:'.$highestColumn.'1' => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
            'A2:'.$highestColumn.'2' => [
                'font' => ['bold' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
            'A3:'.$highestColumn.$lastRow => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
            'A'.$lastRow.':'.$highestColumn.$lastRow => [
                'font' => ['bold' => true],
            ],
        ];
    }
}
