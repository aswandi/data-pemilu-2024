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

class VoteDataSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, WithTitle
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
            $vote->pro_nama ?? '',
            $vote->pro_kode ?? '',
            $vote->kab_nama ?? '',
            $vote->kab_kode ?? '',
            $vote->kec_nama,
            $vote->kec_kode ?? '',
            $vote->kel_nama,
            $vote->kel_kode ?? '',
            $vote->jumlah_tps,
            $vote->total_dpt ?? 0
        ];

        // Tambahkan suara per partai
        foreach ($this->parties as $party) {
            if (empty($vote->chart)) {
                $row[] = '-'; // No data available
            } else {
                $chartData = json_decode($vote->chart, true);
                if (isset($chartData[$party->nomor_urut]['jml_suara_total'])) {
                    $row[] = $chartData[$party->nomor_urut]['jml_suara_total']; // Show actual value (including 0)
                } else {
                    $row[] = '-'; // No data for this party
                }
            }
        }

        // Tambahkan total suara
        $totalSuara = 0;
        if (!empty($vote->chart)) {
            $chartData = json_decode($vote->chart, true);
            foreach ($this->parties as $party) {
                if (isset($chartData[$party->nomor_urut]['jml_suara_total'])) {
                    $totalSuara += $chartData[$party->nomor_urut]['jml_suara_total'];
                }
            }
        }
        $row[] = empty($vote->chart) ? '-' : $totalSuara;

        return $row;
    }

    public function headings(): array
    {
        $headings = [
            'NO',
            'PROVINSI',
            'KODE PROV',
            'KAB/KOTA',
            'KODE KAB',
            'KECAMATAN',
            'KODE KEC',
            'KELURAHAN/DESA',
            'KODE DESA',
            'JUMLAH TPS',
            'DPT'
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
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        for ($i = 1; $i <= $highestColumnIndex; $i++) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
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
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J2:' . $sheet->getHighestColumn() . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tambahkan baris total
        $totalRow = $lastRow + 1;

        // Hitung total
        $totalTPS = array_sum(array_column($this->voteData, 'jumlah_tps'));
        $totalDPT = array_sum(array_map(function($vote) { return $vote->total_dpt ?? 0; }, $this->voteData));
        $totalPerPartai = [];
        $grandTotal = 0;

        foreach ($this->parties as $party) {
            $total = 0;
            foreach ($this->voteData as $vote) {
                if (!empty($vote->chart)) {
                    $chartData = json_decode($vote->chart, true);
                    if (isset($chartData[$party->nomor_urut]['jml_suara_total'])) {
                        $total += $chartData[$party->nomor_urut]['jml_suara_total'];
                    }
                }
            }
            $totalPerPartai[] = $total;
            $grandTotal += $total;
        }

        // Set nilai total
        $sheet->setCellValue('A' . $totalRow, '');
        $sheet->setCellValue('B' . $totalRow, 'TOTAL ' . strtoupper($this->kabupatenName));
        $sheet->setCellValue('C' . $totalRow, '');
        $sheet->setCellValue('D' . $totalRow, '');
        $sheet->setCellValue('E' . $totalRow, '');
        $sheet->setCellValue('F' . $totalRow, '');
        $sheet->setCellValue('G' . $totalRow, '');
        $sheet->setCellValue('H' . $totalRow, '');
        $sheet->setCellValue('I' . $totalRow, '');
        $sheet->setCellValue('J' . $totalRow, $totalTPS);
        $sheet->setCellValue('K' . $totalRow, $totalDPT);

        $col = 'L';
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
        $sheet->mergeCells('B' . $totalRow . ':I' . $totalRow);

        // Tambahkan nomor urut
        for ($i = 2; $i <= $lastRow; $i++) {
            $sheet->setCellValue('A' . $i, $i - 1);
        }

        return [];
    }

    public function columnFormats(): array
    {
        $formats = [
            'A' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
        ];

        // Format party vote columns to show zeros
        $startColIndex = 12; // Column L (0-based: A=1, B=2, ..., L=12)
        for ($i = 0; $i < count($this->parties); $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColIndex + $i);
            $formats[$col] = '0;-0;0;@'; // Custom format: positive;negative;zero;text
        }

        // Format total vote column
        $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColIndex + count($this->parties));
        $formats[$totalCol] = '0;-0;0;@';

        return $formats;
    }

    public function title(): string
    {
        return 'Ringkasan ' . $this->kabupatenName;
    }
}