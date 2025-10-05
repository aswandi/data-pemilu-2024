<?php

namespace App\Exports;

use App\Models\VoteData;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KabupatenCalegSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;
    protected $kecamatanSummary;
    protected $parties;

    public function __construct($kabupatenId, $kabupatenName, $provinceName)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;

        $this->parties = VoteData::getPartyData();
        $this->loadKecamatanSummary();
    }

    protected function loadKecamatanSummary()
    {
        try {
            // Get all kecamatan in this kabupaten (limit to prevent timeout)
            $kecamatanData = Province::getKecamatanDataWithStats($this->kabupatenId);

            $this->kecamatanSummary = collect();
            $processedCount = 0;
            $maxKecamatan = 10; // Limit to prevent timeout

            foreach($kecamatanData as $kecamatan) {
                if ($processedCount >= $maxKecamatan) {
                    break;
                }

                // Get basic summary data for this kecamatan
                $kecamatanTotalVotes = [];

                // Initialize party vote counts
                foreach($this->parties as $party) {
                    $kecamatanTotalVotes[$party->nomor_urut] = 0;
                }

                $this->kecamatanSummary->push([
                    'kecamatan' => $kecamatan,
                    'kelurahan_count' => intval($kecamatan->jumlah_kelurahan ?? 0),
                    'tps_count' => intval($kecamatan->jumlah_tps ?? 0),
                    'dpt_count' => intval($kecamatan->total_dpt ?? 0),
                    'party_votes' => $kecamatanTotalVotes
                ]);

                $processedCount++;
            }
        } catch (\Exception $e) {
            // Fallback with basic data
            $this->kecamatanSummary = collect([
                [
                    'kecamatan' => (object)['nama_kecamatan' => 'Data Loading Error'],
                    'kelurahan_count' => 0,
                    'tps_count' => 0,
                    'dpt_count' => 0,
                    'party_votes' => []
                ]
            ]);
        }
    }

    public function collection()
    {
        return $this->kecamatanSummary;
    }

    public function headings(): array
    {
        $headings = [
            'No',
            'Kecamatan',
            'Jumlah Kelurahan',
            'Jumlah TPS',
            'Total DPT'
        ];

        // Add party columns
        foreach($this->parties as $party) {
            $headings[] = $party->partai_singkat ?? $party->nama;
        }

        $headings[] = 'Total Suara';

        return $headings;
    }

    public function map($item): array
    {
        static $counter = 0;
        $counter++;

        $row = [
            $counter,
            $item['kecamatan']->nama_kecamatan,
            number_format($item['kelurahan_count'], 0, ',', '.'),
            number_format($item['tps_count'], 0, ',', '.'),
            number_format($item['dpt_count'], 0, ',', '.')
        ];

        $totalSuara = 0;

        // Add party vote columns
        foreach($this->parties as $party) {
            $suara = $item['party_votes'][$party->nomor_urut] ?? 0;
            $row[] = number_format($suara, 0, ',', '.');
            $totalSuara += $suara;
        }

        $row[] = number_format($totalSuara, 0, ',', '.');

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->kecamatanSummary->count() + 1; // +1 for header
        $lastColumn = chr(69 + $this->parties->count()); // E + number of parties + total column

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
            "A2:{$lastColumn}{$lastRow}" => [
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
            // Left align kecamatan names
            "B2:B{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Suara per Kecamatan - ' . $this->kabupatenName;
    }
}