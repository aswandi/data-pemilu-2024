<?php

namespace App\Exports;

use App\Models\VoteData;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class VoteDataExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, WithTitle
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;
    protected $parties;
    protected $voteData;

    public function __construct($kabupatenId, $kabupatenName, $provinceName)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;
        $this->parties = VoteData::getPartyData();
        $this->voteData = VoteData::getVoteDataByKabupaten($kabupatenId);
    }

    public function collection()
    {
        return collect($this->voteData);
    }

    public function map($vote): array
    {
        $row = [
            '', // No (akan diisi di headings)
            $vote->kec_nama,
            $vote->kel_nama,
            $vote->jumlah_tps
        ];

        // Tambahkan suara per partai
        foreach ($this->parties as $party) {
            $row[] = VoteData::getSuaraPartai($vote->chart, $party->nomor_urut);
        }

        // Tambahkan total suara
        $totalSuara = 0;
        foreach ($this->parties as $party) {
            $totalSuara += VoteData::getSuaraPartai($vote->chart, $party->nomor_urut);
        }
        $row[] = $totalSuara;

        return $row;
    }

    public function headings(): array
    {
        $headings = [
            'NO',
            'KECAMATAN',
            'KELURAHAN/DESA',
            'JUMLAH TPS'
        ];

        // Tambahkan header partai
        foreach ($this->parties as $party) {
            $headings[] = $party->partai_singkat ?: $party->nama;
        }

        $headings[] = 'TOTAL SUARA';

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        // Auto-size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Style header
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // Style data rows
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A2:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // Center align numeric columns
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:' . $sheet->getHighestColumn() . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tambahkan baris total
        $totalRow = $lastRow + 1;

        // Hitung total
        $totalTPS = array_sum(array_column($this->voteData, 'jumlah_tps'));
        $totalPerPartai = [];
        $grandTotal = 0;

        foreach ($this->parties as $party) {
            $total = 0;
            foreach ($this->voteData as $vote) {
                $total += VoteData::getSuaraPartai($vote->chart, $party->nomor_urut);
            }
            $totalPerPartai[] = $total;
            $grandTotal += $total;
        }

        // Set nilai total
        $sheet->setCellValue('A' . $totalRow, '');
        $sheet->setCellValue('B' . $totalRow, 'TOTAL ' . strtoupper($this->kabupatenName));
        $sheet->setCellValue('C' . $totalRow, '');
        $sheet->setCellValue('D' . $totalRow, $totalTPS);

        $col = 'E';
        foreach ($totalPerPartai as $total) {
            $sheet->setCellValue($col . $totalRow, $total);
            $col++;
        }
        $sheet->setCellValue($col . $totalRow, $grandTotal);

        // Style baris total
        $sheet->getStyle('A' . $totalRow . ':' . $sheet->getHighestColumn() . $totalRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // Merge cells untuk total label
        $sheet->mergeCells('B' . $totalRow . ':C' . $totalRow);

        // Tambahkan nomor urut
        for ($i = 2; $i <= $lastRow; $i++) {
            $sheet->setCellValue('A' . $i, $i - 1);
        }

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function title(): string
    {
        return 'Data Suara DPR RI - ' . $this->kabupatenName;
    }
}