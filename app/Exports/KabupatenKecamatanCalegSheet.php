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

class KabupatenKecamatanCalegSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $kecamatanId;
    protected $kecamatanName;
    protected $kabupatenName;
    protected $provinceName;
    protected $kelurahanVoteData;
    protected $parties;
    protected $calegData;
    protected $exportData;

    public function __construct($kecamatanId, $kecamatanName, $kabupatenName, $provinceName)
    {
        $this->kecamatanId = $kecamatanId;
        $this->kecamatanName = $kecamatanName;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;

        try {
            $this->parties = VoteData::getPartyData();
            $this->calegData = VoteData::getCalegData();
            $this->loadKelurahanVoteData();
            $this->prepareExportData();
        } catch (\Exception $e) {
            // Initialize with empty data if there's an error
            $this->parties = collect();
            $this->calegData = collect();
            $this->kelurahanVoteData = collect();
            $this->exportData = collect();
        }
    }

    protected function loadKelurahanVoteData()
    {
        // Get all kelurahan in this kecamatan
        $kelurahanData = Province::getKelurahanDataWithStats($this->kecamatanId);

        $this->kelurahanVoteData = collect();
        $kelurahanCount = 0;
        $maxKelurahan = 15; // Limit to prevent timeout

        foreach($kelurahanData as $kelurahan) {
            if ($kelurahanCount >= $maxKelurahan) {
                break;
            }

            $voteData = VoteData::getVoteDataByKelurahan($kelurahan->id);
            if (!empty($voteData)) {
                $this->kelurahanVoteData->push([
                    'kelurahan_info' => $kelurahan,
                    'vote_data' => $voteData[0]
                ]);
                $kelurahanCount++;
            }
        }
    }

    protected function prepareExportData()
    {
        $this->exportData = collect();

        // Add party total rows
        foreach($this->parties as $party) {
            $this->exportData->push([
                'type' => 'party',
                'id' => $party->nomor_urut,
                'name' => 'TOTAL ' . ($party->partai_singkat ?? $party->nama),
                'partai_id' => $party->nomor_urut,
                'is_total' => true
            ]);
        }

        // Add candidate rows (limit to prevent timeout)
        $calegCount = 0;
        $maxCaleg = 50; // Limit candidates

        foreach($this->calegData as $caleg) {
            if ($calegCount >= $maxCaleg) {
                break;
            }

            // Only include candidates with votes in this kecamatan
            $hasVotes = false;
            foreach($this->kelurahanVoteData as $kelData) {
                $tbl = json_decode($kelData['vote_data']->tbl, true) ?? [];
                foreach($tbl as $tpsData) {
                    if (isset($tpsData[$caleg->id]) && intval($tpsData[$caleg->id]) > 0) {
                        $hasVotes = true;
                        break 2;
                    }
                }
            }

            if ($hasVotes) {
                $this->exportData->push([
                    'type' => 'caleg',
                    'id' => $caleg->id,
                    'name' => $caleg->nama,
                    'partai_id' => $caleg->partai_id,
                    'nomor_urut' => $caleg->nomor_urut,
                    'is_total' => false
                ]);
                $calegCount++;
            }
        }

        // Sort by party, then by type (party totals first), then by nomor urut
        $this->exportData = $this->exportData->sort(function($a, $b) {
            if ($a['partai_id'] !== $b['partai_id']) {
                return $a['partai_id'] - $b['partai_id'];
            }
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'party' ? -1 : 1;
            }
            if (isset($a['nomor_urut']) && isset($b['nomor_urut'])) {
                return $a['nomor_urut'] - $b['nomor_urut'];
            }
            return 0;
        });
    }

    public function collection()
    {
        return $this->exportData;
    }

    public function headings(): array
    {
        $headings = [
            'Jenis',
            'Nama Partai/Caleg',
            'No. Urut'
        ];

        // Add village columns
        foreach($this->kelurahanVoteData as $kelData) {
            $headings[] = $kelData['kelurahan_info']->nama_kelurahan;
        }

        $headings[] = 'Total';

        return $headings;
    }

    public function map($item): array
    {
        $row = [
            $item['type'] === 'party' ? 'PARTAI' : 'CALEG',
            $item['name'],
            $item['type'] === 'party' ? $item['partai_id'] : ($item['nomor_urut'] ?? '')
        ];

        $totalVotes = 0;

        // Add vote data for each kelurahan
        foreach($this->kelurahanVoteData as $kelData) {
            $votes = 0;

            if ($item['type'] === 'party') {
                // Get party votes from chart
                $chart = json_decode($kelData['vote_data']->chart, true) ?? [];
                $votes = isset($chart[$item['partai_id']]) ? intval($chart[$item['partai_id']]) : 0;
            } else {
                // Get candidate votes from tbl
                $tbl = json_decode($kelData['vote_data']->tbl, true) ?? [];
                foreach($tbl as $tpsData) {
                    if (isset($tpsData[$item['id']])) {
                        $votes += intval($tpsData[$item['id']]);
                    }
                }
            }

            $row[] = number_format($votes, 0, ',', '.');
            $totalVotes += $votes;
        }

        $row[] = number_format($totalVotes, 0, ',', '.');

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->exportData->count() + 1; // +1 for header
        $lastColumn = chr(67 + $this->kelurahanVoteData->count()); // C + number of kelurahan + total

        $styles = [
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
            // Left align names
            "B2:B{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];

        // Add styling for party total rows (bold and highlighted)
        $currentRow = 2; // Start from row 2 (after header)
        foreach($this->exportData as $item) {
            if ($item['is_total']) {
                $styles["A{$currentRow}:{$lastColumn}{$currentRow}"] = [
                    'font' => [
                        'bold' => true
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6']
                    ]
                ];
            }
            $currentRow++;
        }

        return $styles;
    }

    public function title(): string
    {
        return $this->kecamatanName;
    }
}