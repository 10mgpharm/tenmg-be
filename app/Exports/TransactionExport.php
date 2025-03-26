<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

//Reference: https://docs.laravel-excel.com/3.1/exports/

class TransactionExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public $companySettings;

    public function __construct(public \Illuminate\Database\Eloquent\Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function prepareRows($rows)
    {
        return $rows->transform(function ($transaction) {
            //can modify or access extra infor here
            return $transaction;
        });
    }

    public function map($transaction): array
    {
        return [
            $transaction->identifier,
            $transaction->amount,
            $transaction->type,
            $transaction->transaction_group,
            $transaction->description,
            $transaction->status,
            $transaction->payment_method,
            $transaction->reference,
            $transaction->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            'Identifier',
            'Amount',
            'Type',
            'Transaction Group',
            'Description',
            'Status',
            'Payment Method',
            'Reference',
            'Date'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 15,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 20
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // $color = strtoupper(str_replace('#', '', $this->companySettings->theme_primary));

        // return [
        //     // Style the first row as bold text..
        //     1 => [
        //         'font' => [
        //             'bold' => true,
        //             'size' => 12,
        //             'color' => ['rgb' => 'FFFFFF'],
        //         ], // Font size and color
        //         'fill' => [
        //             'fillType' => Fill::FILL_SOLID,
        //             'startColor' => ['rgb' => $color],
        //         ],
        //         'alignment' => [
        //             'horizontal' => Alignment::HORIZONTAL_LEFT,
        //             'vertical' => Alignment::VERTICAL_CENTER,
        //         ],
        //         'borders' => [
        //             'allBorders' => [
        //                 'borderStyle' => Border::BORDER_THIN,
        //                 // 'color' => ['rgb' => '000000'],
        //             ],
        //         ],
        //     ],

            // Styling a specific cell by coordinate.
            // 'B2' => ['font' => ['italic' => true]],

            // Styling an entire column.
            // 'C'  => ['font' => ['size' => 16]],
        // ];
    }
}
