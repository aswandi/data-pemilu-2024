<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KabupatenNoDataSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $kabupatenName;
    protected $message;

    public function __construct($kabupatenName, $message = null)
    {
        $this->kabupatenName = $kabupatenName;
        $this->message = $message ?? 'Tidak ada data suara caleg yang tersedia untuk kabupaten ini.';
    }

    public function array(): array
    {
        return [
            ['1', $this->message, '-', '-'],
            ['2', 'Silakan coba kabupaten lain atau hubungi administrator.', '-', '-'],
            ['3', 'Tanggal Export: ' . now()->format('d/m/Y H:i:s'), '-', '-'],
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Keterangan',
            'Status',
            'Info'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
            // All data rows
            'A2:D4' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            // Left align messages
            'B2:B4' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];
    }

    public function title(): string
    {
        return 'No Data - ' . $this->kabupatenName;
    }
}