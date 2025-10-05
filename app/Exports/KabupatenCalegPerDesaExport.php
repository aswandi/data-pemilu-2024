<?php

namespace App\Exports;

use App\Models\VoteData;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KabupatenCalegPerDesaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;
    protected $kelurahanVoteData;
    protected $parties;
    protected $calegWithVotes;
    protected $exportData;
    protected $dapilInfo;

    public function __construct($kabupatenId, $kabupatenName, $provinceName)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;

        $this->parties = VoteData::getPartyData();
        // Try to get dapil from hr_dpr_ri_kel first (most accurate)
        $this->dapilInfo = VoteData::getDapilByKabupatenFromHR($this->kabupatenId);

        // Fallback to old method if no data in hr_dpr_ri_kel
        if (empty($this->dapilInfo)) {
            $this->dapilInfo = VoteData::getDapilByKabupaten($this->kabupatenId);
        }
        $this->loadKelurahanVoteData();
        $this->loadCalegWithVotes();
        $this->prepareExportData();
    }

    protected function loadKelurahanVoteData()
    {
        // Get all kecamatan in this kabupaten
        $kecamatanData = Province::getKecamatanDataWithStats($this->kabupatenId);

        $this->kelurahanVoteData = collect();

        foreach($kecamatanData as $kecamatan) {
            // Get all kelurahan in this kecamatan
            $kelurahanData = Province::getKelurahanDataWithStats($kecamatan->id);

            foreach($kelurahanData as $kelurahan) {
                $voteData = VoteData::getVoteDataByKelurahan($kelurahan->id);
                if (!empty($voteData)) {
                    $this->kelurahanVoteData->push([
                        'kelurahan_info' => $kelurahan,
                        'kecamatan_info' => $kecamatan,
                        'vote_data' => $voteData[0]
                    ]);
                }
            }
        }
    }

    protected function loadCalegWithVotes()
    {
        // Use the accurate dapil data from hr_dpr_ri_kel
        $dapilIds = array_column($this->dapilInfo, 'dapil_id');

        // Get caleg data filtered by dapil
        if (!empty($dapilIds)) {
            $calegData = VoteData::getCalegDataByDapil($dapilIds);
        } else {
            // Fallback to all caleg if no dapil found
            $calegData = VoteData::getCalegData();
        }

        $this->calegWithVotes = collect();

        // Pre-process all TPS data for efficiency
        $allTpsData = [];
        foreach($this->kelurahanVoteData as $kelIndex => $kelData) {
            $tbl = json_decode($kelData['vote_data']->tbl, true) ?? [];
            $allTpsData[$kelIndex] = $tbl;
        }

        $maxCaleg = 200; // Show more candidates but still manageable
        $calegCount = 0;

        foreach($calegData as $caleg) {
            if ($calegCount >= $maxCaleg) {
                break;
            }

            $totalSuara = 0;

            // Calculate total votes for this caleg across all kelurahan (optimized)
            foreach($allTpsData as $kelIndex => $tpsData) {
                foreach($tpsData as $tpsId => $tpsVotes) {
                    if (isset($tpsVotes[$caleg->id])) {
                        $totalSuara += intval($tpsVotes[$caleg->id]);
                    }
                }
            }

            // Include caleg with any votes or first 100 regardless of votes
            if ($totalSuara > 0 || $calegCount < 100) {
                $caleg->total_suara = $totalSuara;
                $this->calegWithVotes->push($caleg);
                $calegCount++;
            }

            // Add memory cleanup for large datasets
            if ($calegCount % 50 === 0) {
                gc_collect_cycles(); // Force garbage collection
            }
        }

        // Sort by party number first, then by caleg number
        $this->calegWithVotes = $this->calegWithVotes->sort(function($a, $b) {
            if ($a->partai_id !== $b->partai_id) {
                return $a->partai_id - $b->partai_id;
            }
            return $a->nomor_urut - $b->nomor_urut;
        });
    }

    protected function prepareExportData()
    {
        $this->exportData = collect();

        // Group candidates by party and add party totals
        $groupedByParty = $this->calegWithVotes->groupBy('partai_id');

        foreach($groupedByParty as $partaiId => $calegs) {
            // Add party total row
            $this->exportData->push([
                'type' => 'party',
                'partai_id' => $partaiId,
                'name' => 'TOTAL ' . $this->getPartaiName($partaiId),
                'nomor_urut' => '',
                'dapil_id' => '',
                'dapil_nama' => '',
                'is_total' => true
            ]);

            // Add party-only votes row (suara partai saja)
            $this->exportData->push([
                'type' => 'party_only',
                'partai_id' => $partaiId,
                'name' => 'SUARA PARTAI SAJA - ' . $this->getPartaiName($partaiId),
                'nomor_urut' => '',
                'dapil_id' => '',
                'dapil_nama' => '',
                'is_total' => false
            ]);

            // Add candidates for this party
            foreach($calegs as $caleg) {
                $this->exportData->push([
                    'type' => 'caleg',
                    'id' => $caleg->id,
                    'partai_id' => $caleg->partai_id,
                    'name' => $caleg->nama,
                    'nomor_urut' => $caleg->nomor_urut,
                    'dapil_id' => $caleg->dapil_id ?? '',
                    'dapil_nama' => $caleg->dapil_nama ?? '',
                    'is_total' => false
                ]);
            }
        }
    }

    public function collection()
    {
        return $this->exportData;
    }

    public function headings(): array
    {
        // This will be row 2 (desa names)
        $headings = [
            'Jenis',
            'No. Partai',
            'Nama Partai/Caleg',
            'No. Urut',
            'Dapil'
        ];

        // Add village columns (desa names only)
        foreach($this->kelurahanVoteData as $kelData) {
            $headings[] = $kelData['kelurahan_info']->nama_kelurahan;
        }

        $headings[] = 'Total Suara';
        $headings[] = 'Suara Partai Saja';

        return $headings;
    }

    public function map($item): array
    {
        $row = [
            $item['type'] === 'party' ? 'PARTAI' : ($item['type'] === 'party_only' ? 'PARTAI SAJA' : 'CALEG'),
            $item['partai_id'],
            $item['name'],
            $item['nomor_urut'],
            $item['dapil_nama'] ?? ''
        ];

        $totalVotes = 0;
        $totalPartyOnlyVotes = 0;

        // Add vote data for each kelurahan
        foreach($this->kelurahanVoteData as $kelData) {
            $votes = null; // Start with null to detect missing data
            $hasData = false;

            if ($item['type'] === 'party') {
                // For party total: sum all caleg votes + party-only votes
                $chartJson = $kelData['vote_data']->chart ?? '';
                $tblJson = $kelData['vote_data']->tbl ?? '';

                // Get party-only votes from chart
                $partyOnlyVotes = 0;
                if (!empty($chartJson) && $chartJson !== '""' && $chartJson !== '[]') {
                    $chart = json_decode($chartJson, true) ?? [];
                    if (is_array($chart) && isset($chart[$item['partai_id']]['jml_suara_partai'])) {
                        $partyOnlyVotes = intval($chart[$item['partai_id']]['jml_suara_partai']);
                    }
                }

                // Get caleg votes for this party from tbl (using pre-computed caleg list for efficiency)
                $partyCandidateVotes = 0;
                if (!empty($tblJson) && $tblJson !== '""' && $tblJson !== '[]') {
                    $tbl = json_decode($tblJson, true) ?? [];
                    // Get caleg IDs for this party (cache for efficiency)
                    $partyCalegIds = $this->calegWithVotes->where('partai_id', $item['partai_id'])->pluck('id')->toArray();

                    foreach($tbl as $tpsData) {
                        if (is_array($tpsData)) {
                            foreach($partyCalegIds as $calegId) {
                                if (isset($tpsData[$calegId])) {
                                    $partyCandidateVotes += intval($tpsData[$calegId]);
                                }
                            }
                        }
                    }
                }

                // Total party votes = party-only + candidate votes
                $votes = $partyOnlyVotes + $partyCandidateVotes;
                $hasData = $votes > 0 || $partyOnlyVotes >= 0; // Consider as having data if we can calculate
                $totalPartyOnlyVotes += $partyOnlyVotes;
            } elseif ($item['type'] === 'party_only') {
                // For party-only: show only party votes (no candidate votes)
                $chartJson = $kelData['vote_data']->chart ?? '';
                $partyOnlyVotes = 0;
                if (!empty($chartJson) && $chartJson !== '""' && $chartJson !== '[]') {
                    $chart = json_decode($chartJson, true) ?? [];
                    if (is_array($chart) && isset($chart[$item['partai_id']]['jml_suara_partai'])) {
                        $partyOnlyVotes = intval($chart[$item['partai_id']]['jml_suara_partai']);
                    }
                }
                $votes = $partyOnlyVotes;
                $hasData = $partyOnlyVotes >= 0; // Consider as having data if we can get party votes
                $totalPartyOnlyVotes += $partyOnlyVotes;
            } else {
                // Get candidate votes from tbl
                $tbl = json_decode($kelData['vote_data']->tbl, true) ?? [];
                $votes = null; // Start with null to detect missing data
                $candidateVotes = 0; // Track actual vote count

                foreach($tbl as $tpsData) {
                    if (isset($tpsData[$item['id']])) {
                        $candidateVotes += intval($tpsData[$item['id']]);
                        $hasData = true;
                    }
                }

                // Only set votes if we found data
                if ($hasData) {
                    $votes = $candidateVotes;
                }
            }

            // Display logic: "-" for missing data, "0" for actual zero values
            if (!$hasData || $votes === null) {
                $row[] = '-';
                // Don't add to total if no data
            } else {
                $row[] = number_format($votes, 0, ',', '.');
                $totalVotes += $votes;
            }
        }

        $row[] = number_format($totalVotes, 0, ',', '.');

        // Add party-only votes column (only show for party and party_only rows, empty for caleg rows)
        if ($item['type'] === 'party') {
            $row[] = number_format($totalPartyOnlyVotes, 0, ',', '.');
        } elseif ($item['type'] === 'party_only') {
            $row[] = number_format($totalPartyOnlyVotes, 0, ',', '.');
        } else {
            $row[] = '-'; // No party-only votes for individual candidates
        }

        return $row;
    }

    protected function getPartaiName($partaiId)
    {
        foreach($this->parties as $party) {
            if ($party->nomor_urut == $partaiId) {
                return $party->partai_singkat ?? $party->nama;
            }
        }
        return "Partai $partaiId";
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->exportData->count() + 2; // +2 for two header rows
        $lastColumn = chr(70 + $this->kelurahanVoteData->count()); // F + number of kelurahan + total + party-only (adjusted for dapil column)

        $styles = [
            // All data rows (starting from row 3 now)
            "A3:{$lastColumn}{$lastRow}" => [
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
            // Left align names (starting from row 3)
            "C3:C{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ],
            // Left align dapil names (starting from row 3)
            "E3:E{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];

        // Add styling for party total rows (bold and highlighted)
        $currentRow = 3; // Start from row 3 (after two header rows)
        foreach($this->exportData as $item) {
            if ($item['is_total']) {
                // Bold styling for party total rows
                $styles["A{$currentRow}:{$lastColumn}{$currentRow}"] = [
                    'font' => [
                        'bold' => true
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6']
                    ]
                ];
            } elseif ($item['type'] === 'party_only') {
                // Italic styling for party-only rows
                $styles["A{$currentRow}:{$lastColumn}{$currentRow}"] = [
                    'font' => [
                        'italic' => true
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7'] // Light yellow background
                    ]
                ];
            }
            $currentRow++;
        }

        return $styles;
    }

    public function title(): string
    {
        $dapilText = '';
        if (!empty($this->dapilInfo)) {
            $dapilNames = array_column($this->dapilInfo, 'dapil_nama');
            $dapilText = ' (' . implode(', ', $dapilNames) . ')';
        }
        return 'Data Suara Caleg per Desa - ' . $this->kabupatenName . $dapilText;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insert a new row at the top for kecamatan headers
                $sheet->insertNewRowBefore(1, 1);

                // Create kecamatan header row (row 1)
                $kecamatanHeaders = [
                    '', '', '', '', '' // Empty cells for first 5 columns
                ];

                // Group villages by kecamatan and create merged headers
                $currentCol = 6; // Start after the 5 fixed columns (F column)
                $groupedByKecamatan = $this->kelurahanVoteData->groupBy('kecamatan_info.nama_kecamatan');

                foreach($groupedByKecamatan as $kecamatanName => $kelurahanInKecamatan) {
                    $kelurahanCount = $kelurahanInKecamatan->count();

                    // Set kecamatan name in first cell of the group
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                    $sheet->setCellValue($colLetter . '1', $kecamatanName);

                    // Merge cells for this kecamatan if it has multiple kelurahan
                    if ($kelurahanCount > 1) {
                        $endColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol + $kelurahanCount - 1);
                        $sheet->mergeCells($colLetter . '1:' . $endColLetter . '1');
                    }

                    $currentCol += $kelurahanCount;
                }

                // Add headers for Total Suara and Suara Partai Saja
                $totalColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                $partaiColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol + 1);
                $sheet->setCellValue($totalColLetter . '1', 'Total Suara');
                $sheet->setCellValue($partaiColLetter . '1', 'Suara Partai Saja');

                // Style the kecamatan header row
                $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol + 1);
                $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D1E7DD'] // Light green background
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
                ]);

                // Style the desa header row (now row 2)
                $sheet->getStyle('A2:' . $lastColumn . '2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB'] // Light gray background
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
            }
        ];
    }
}