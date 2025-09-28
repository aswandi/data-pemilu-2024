<?php

namespace App\Exports;

use App\Models\VoteData;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KecamatanPartaiWebSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $kecamatanId;
    protected $kecamatanName;
    protected $kabupatenName;
    protected $provinceName;
    protected $kelurahanVoteData;
    protected $parties;

    public function __construct($kecamatanId, $kecamatanName, $kabupatenName, $provinceName)
    {
        $this->kecamatanId = $kecamatanId;
        $this->kecamatanName = $kecamatanName;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;

        // Get kelurahan vote data and parties using same method as web
        $this->parties = VoteData::getPartyData();
        $this->loadKelurahanVoteData();
    }

    protected function loadKelurahanVoteData()
    {
        // Get all kelurahan in this kecamatan
        $kelurahanData = \App\Models\Province::getKelurahanDataWithStats($this->kecamatanId);

        $this->kelurahanVoteData = collect();
        foreach($kelurahanData as $kelurahan) {
            $voteData = VoteData::getVoteDataByKelurahan($kelurahan->id);
            if (!empty($voteData)) {
                $this->kelurahanVoteData->push([
                    'kelurahan_info' => $kelurahan,
                    'vote_data' => $voteData[0]
                ]);
            }
        }
    }

    public function collection()
    {
        // Return kelurahan vote data plus a total row
        $data = $this->kelurahanVoteData->toArray();

        // Add total row
        $data[] = $this->getTotalRow();

        return collect($data);
    }

    protected function getTotalRow()
    {
        $totalTPS = $this->kelurahanVoteData->sum(function($kelData) {
            return $kelData['vote_data']->jumlah_tps;
        });

        $totalDPT = $this->kelurahanVoteData->sum(function($kelData) {
            return $kelData['vote_data']->total_dpt ?? 0;
        });

        // Calculate total votes per party
        $totalPerPartai = [];
        for ($i = 1; $i <= 18; $i++) {
            $totalPerPartai[$i] = $this->kelurahanVoteData->sum(function($kelData) use ($i) {
                $chart = json_decode($kelData['vote_data']->chart, true) ?? [];
                return isset($chart[$i]['jml_suara_partai']) ? intval($chart[$i]['jml_suara_partai']) : 0;
            });
        }

        $grandTotal = array_sum($totalPerPartai);

        return [
            'is_total' => true,
            'kelurahan_info' => (object)[
                'nama_kelurahan' => "TOTAL {$this->kecamatanName}"
            ],
            'vote_data' => (object)[
                'jumlah_tps' => $totalTPS,
                'total_dpt' => $totalDPT,
                'chart' => json_encode(['partai' => $totalPerPartai]),
                'grand_total' => $grandTotal
            ]
        ];
    }

    public function headings(): array
    {
        $headings = [
            'No',
            'Kelurahan/Desa',
            'Jumlah TPS',
            'DPT'
        ];

        // Add party columns
        foreach($this->parties as $party) {
            $headings[] = $party->partai_singkat ?? $party->nama;
        }

        $headings[] = 'Total Suara';

        return $headings;
    }

    public function map($kelData): array
    {
        static $counter = 0;

        if (isset($kelData['is_total']) && $kelData['is_total']) {
            // Total row
            $row = [
                '',
                $kelData['kelurahan_info']->nama_kelurahan,
                $kelData['vote_data']->jumlah_tps,
                number_format($kelData['vote_data']->total_dpt, 0, ',', '.')
            ];

            $chart = json_decode($kelData['vote_data']->chart, true) ?? [];
            for ($i = 1; $i <= 18; $i++) {
                $suara = isset($chart['partai'][$i]) ? intval($chart['partai'][$i]) : 0;
                $row[] = number_format($suara, 0, ',', '.');
            }

            $row[] = number_format($kelData['vote_data']->grand_total, 0, ',', '.');

            return $row;
        } else {
            // Regular row
            $counter++;
            $row = [
                $counter,
                $kelData['kelurahan_info']->nama_kelurahan,
                $kelData['vote_data']->jumlah_tps,
                number_format($kelData['vote_data']->total_dpt ?? 0, 0, ',', '.')
            ];

            $chart = json_decode($kelData['vote_data']->chart, true) ?? [];
            $totalSuara = 0;

            for ($i = 1; $i <= 18; $i++) {
                $suara = isset($chart[$i]['jml_suara_partai']) ? intval($chart[$i]['jml_suara_partai']) : 0;
                $row[] = number_format($suara, 0, ',', '.');
                $totalSuara += $suara;
            }

            $row[] = number_format($totalSuara, 0, ',', '.');

            return $row;
        }
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->kelurahanVoteData->count() + 2; // +1 for header, +1 for total row
        $lastColumn = chr(68 + count($this->parties)); // D + number of parties

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
            // Total row styling
            $lastRow => [
                'font' => [
                    'bold' => true,
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5']
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
            // Left align kelurahan names
            "B2:B{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];
    }

    public function title(): string
    {
        return 'Data Suara Partai per Kelurahan';
    }
}