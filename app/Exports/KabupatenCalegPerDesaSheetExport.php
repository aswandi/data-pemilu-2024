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

class KabupatenCalegPerDesaSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;
    protected $kelurahanVoteData;
    protected $parties;
    protected $calegWithVotes;
    protected $exportData;
    protected $dapilInfo;
    protected $sheetTitle;
    protected $isLastSheet;

    public function __construct($kabupatenId, $kabupatenName, $provinceName, $kelurahanChunk, $sheetTitle, $isLastSheet = true)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;
        $this->kelurahanVoteData = $kelurahanChunk;
        $this->sheetTitle = $sheetTitle;
        $this->isLastSheet = $isLastSheet;

        $this->parties = VoteData::getPartyData();

        // Try to get dapil from hr_dpr_ri_kel first (most accurate)
        $this->dapilInfo = VoteData::getDapilByKabupatenFromHR($this->kabupatenId);

        // Fallback to old method if no data in hr_dpr_ri_kel
        if (empty($this->dapilInfo)) {
            $this->dapilInfo = VoteData::getDapilByKabupaten($this->kabupatenId);
        }

        $this->loadCalegWithVotes();
        $this->prepareExportData();
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
            $tblData = $kelData['vote_data']->tbl ?? '';

            // Handle empty or invalid JSON data
            if (empty($tblData) || $tblData === '""' || $tblData === '[]') {
                $tbl = [];
            } else {
                $tbl = json_decode($tblData, true);
                // If json_decode fails, set to empty array
                if (!is_array($tbl)) {
                    $tbl = [];
                }
            }

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

        // Add total row at the end
        $this->exportData->push([
            'type' => 'total_row',
            'partai_id' => '',
            'name' => 'TOTAL KESELURUHAN',
            'nomor_urut' => '',
            'dapil_id' => '',
            'dapil_nama' => '',
            'is_total' => true,
            'is_grand_total' => true
        ]);
    }

    public function collection()
    {
        return $this->exportData;
    }

    public function headings(): array
    {
        // This will be row 4 (after kecamatan row 1, kode kec row 2, kode desa row 3)
        $headings = [
            'Provinsi',
            'Kode Prov',
            'Kab/Kota',
            'Kode Kab',
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

        // Only add total column in the last sheet
        if ($this->isLastSheet) {
            $headings[] = 'Total Suara';
        }

        return $headings;
    }

    public function map($item): array
    {
        // Get regional info from first kelurahan (all have same provinsi and kabupaten)
        $firstKelurahan = $this->kelurahanVoteData->first();

        $row = [
            $firstKelurahan['provinsi_info']->nama_provinsi ?? '',
            $firstKelurahan['provinsi_info']->pro_kode ?? '',
            $firstKelurahan['kabupaten_info']->nama_kabupaten ?? '',
            $firstKelurahan['kabupaten_info']->kab_kode ?? '',
            $item['type'] === 'party' ? 'PARTAI' : ($item['type'] === 'party_only' ? 'PARTAI SAJA' : ($item['type'] === 'total_row' ? 'TOTAL' : 'CALEG')),
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

            if ($item['type'] === 'total_row') {
                // For total row: sum all votes across all parties and candidates for this kelurahan
                $chartJson = $kelData['vote_data']->chart ?? '';
                $tblJson = $kelData['vote_data']->tbl ?? '';

                $totalKelurahanVotes = 0;

                // Sum party-only votes
                if (!empty($chartJson) && $chartJson !== '""' && $chartJson !== '[]') {
                    $chart = json_decode($chartJson, true);
                    if (is_array($chart)) {
                        foreach($chart as $partyData) {
                            if (is_array($partyData) && isset($partyData['jml_suara_partai'])) {
                                $totalKelurahanVotes += intval($partyData['jml_suara_partai']);
                            }
                        }
                    }
                }

                // Sum all candidate votes
                if (!empty($tblJson) && $tblJson !== '""' && $tblJson !== '[]') {
                    $tbl = json_decode($tblJson, true);
                    if (is_array($tbl)) {
                        foreach($tbl as $tpsData) {
                            if (is_array($tpsData)) {
                                foreach($tpsData as $calegVotes) {
                                    $totalKelurahanVotes += intval($calegVotes);
                                }
                            }
                        }
                    }
                }

                $votes = $totalKelurahanVotes;
                $hasData = true;
            } elseif ($item['type'] === 'party') {
                // For party total: sum all caleg votes + party-only votes
                $chartJson = $kelData['vote_data']->chart ?? '';
                $tblJson = $kelData['vote_data']->tbl ?? '';

                // Get party-only votes from chart
                $partyOnlyVotes = 0;
                if (!empty($chartJson) && $chartJson !== '""' && $chartJson !== '[]') {
                    $chart = json_decode($chartJson, true);
                    if (is_array($chart) && isset($chart[$item['partai_id']]['jml_suara_partai'])) {
                        $partyOnlyVotes = intval($chart[$item['partai_id']]['jml_suara_partai']);
                    }
                }

                // Get caleg votes for this party from tbl (using pre-computed caleg list for efficiency)
                $partyCandidateVotes = 0;
                if (!empty($tblJson) && $tblJson !== '""' && $tblJson !== '[]') {
                    $tbl = json_decode($tblJson, true);
                    if (is_array($tbl)) {
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
                    $chart = json_decode($chartJson, true);
                    if (is_array($chart) && isset($chart[$item['partai_id']]['jml_suara_partai'])) {
                        $partyOnlyVotes = intval($chart[$item['partai_id']]['jml_suara_partai']);
                    }
                }
                $votes = $partyOnlyVotes;
                $hasData = $partyOnlyVotes >= 0; // Consider as having data if we can get party votes
                $totalPartyOnlyVotes += $partyOnlyVotes;
            } else {
                // Get candidate votes from tbl
                $tblData = $kelData['vote_data']->tbl ?? '';
                $votes = null; // Start with null to detect missing data
                $candidateVotes = 0; // Track actual vote count

                if (!empty($tblData) && $tblData !== '""' && $tblData !== '[]') {
                    $tbl = json_decode($tblData, true);
                    if (is_array($tbl)) {
                        foreach($tbl as $tpsData) {
                            if (is_array($tpsData) && isset($tpsData[$item['id']])) {
                                $candidateVotes += intval($tpsData[$item['id']]);
                                $hasData = true;
                            }
                        }
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

        // Only add total column in the last sheet
        if ($this->isLastSheet) {
            $row[] = number_format($totalVotes, 0, ',', '.');
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
        $lastRow = $this->exportData->count() + 4; // +4 for four header rows

        // Calculate last column based on whether this is the last sheet
        if ($this->isLastSheet) {
            $lastColumn = chr(73 + $this->kelurahanVoteData->count()); // J + number of kelurahan + total
        } else {
            $lastColumn = chr(72 + $this->kelurahanVoteData->count()); // I + number of kelurahan (no total columns)
        }

        $styles = [
            // All data rows (starting from row 5 now) with subtle borders
            "A5:{$lastColumn}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'] // Light gray borders
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'font' => [
                    'size' => 10,
                    'name' => 'Segoe UI'
                ]
            ],
            // Left align names (starting from row 5)
            "G5:G{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'font' => [
                    'size' => 10
                ]
            ],
            // Left align dapil names (starting from row 5)
            "I5:I{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'font' => [
                    'size' => 9,
                    'color' => ['rgb' => '6B7280'] // Gray text for dapil
                ]
            ],
            // Style numeric columns (vote columns) with better formatting
            "J5:{$lastColumn}{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'font' => [
                    'size' => 10
                ]
            ]
        ];

        // NO background colors for data rows - only borders and alignment

        return $styles;
    }

    /**
     * Helper function to darken a hex color
     */
    private function darkenColor($hexColor, $percent = 15)
    {
        // Convert hex to RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Darken each component
        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));

        // Convert back to hex
        return sprintf('%02X%02X%02X', $r, $g, $b);
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insert rows for kecamatan and kode headers
                $sheet->insertNewRowBefore(1, 3); // Insert 3 rows: row 1 for kecamatan, row 2 for kode kec, row 3 for kode desa

                // Row 1: Kecamatan names
                // Row 2: Kode Kecamatan
                // Row 3: Kode Desa
                // Row 4: Kelurahan/Desa names (original headings)

                // Merge fixed header columns (A-I) across rows 1-3 since they're static
                $sheet->mergeCells('A1:A3');
                $sheet->mergeCells('B1:B3');
                $sheet->mergeCells('C1:C3');
                $sheet->mergeCells('D1:D3');
                $sheet->mergeCells('E1:E3');
                $sheet->mergeCells('F1:F3');
                $sheet->mergeCells('G1:G3');
                $sheet->mergeCells('H1:H3');
                $sheet->mergeCells('I1:I3');

                // Group villages by kecamatan and create merged headers
                $currentCol = 10; // Start after the 9 fixed columns (J column)
                $groupedByKecamatan = $this->kelurahanVoteData->groupBy('kecamatan_info.nama_kecamatan');

                $colIndex = $currentCol;
                foreach($groupedByKecamatan as $kecamatanName => $kelurahanInKecamatan) {
                    $kelurahanCount = $kelurahanInKecamatan->count();

                    // Set kecamatan name in row 1
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($colLetter . '1', $kecamatanName);

                    // Merge cells for this kecamatan if it has multiple kelurahan
                    if ($kelurahanCount > 1) {
                        $endColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + $kelurahanCount - 1);
                        $sheet->mergeCells($colLetter . '1:' . $endColLetter . '1');
                    }

                    // Add kode kec in row 2 and kode desa in row 3
                    $startColIndex = $colIndex;
                    foreach($kelurahanInKecamatan as $kelData) {
                        $kelColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $kecKode = $kelData['kecamatan_info']->kec_kode ?? '';
                        $kelKode = $kelData['kelurahan_info']->kel_kode ?? '';

                        // Row 2: Kode Kecamatan
                        $sheet->setCellValue($kelColLetter . '2', $kecKode);

                        // Row 3: Kode Desa
                        $sheet->setCellValue($kelColLetter . '3', $kelKode);

                        $colIndex++;
                    }

                    // Merge kode kec cells if multiple kelurahan in same kecamatan
                    if ($kelurahanCount > 1) {
                        $endKecColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColIndex + $kelurahanCount - 1);
                        $startKecColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColIndex);
                        $sheet->mergeCells($startKecColLetter . '2:' . $endKecColLetter . '2');
                    }
                }
                $currentCol = $colIndex;

                // Add header for Total Suara only in last sheet
                if ($this->isLastSheet) {
                    $totalColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                    $sheet->setCellValue($totalColLetter . '1', 'Total Suara');
                    $sheet->mergeCells($totalColLetter . '1:' . $totalColLetter . '3');
                    $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                } else {
                    $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol - 1);
                }

                // Style the kecamatan header row (row 1)
                $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '1F2937'], // Dark gray text
                        'name' => 'Segoe UI'
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D1D5DB'] // Gray background
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '9CA3AF'] // Gray border
                        ],
                    ],
                ]);

                // Style the kode kecamatan row (row 2)
                $sheet->getStyle('A2:' . $lastColumn . '2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '374151'], // Dark gray text
                        'name' => 'Segoe UI'
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
                            'color' => ['rgb' => '9CA3AF'] // Gray border
                        ],
                    ],
                ]);

                // Style the kode desa row (row 3)
                $sheet->getStyle('A3:' . $lastColumn . '3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '374151'], // Dark gray text
                        'name' => 'Segoe UI'
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
                            'color' => ['rgb' => '9CA3AF'] // Gray border
                        ],
                    ],
                ]);

                // Style the desa name row (row 4)
                $sheet->getStyle('A4:' . $lastColumn . '4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '374151'], // Dark gray text
                        'name' => 'Segoe UI'
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'] // Very light gray background
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '9CA3AF'] // Gray border
                        ],
                    ],
                ]);

                // Add special styling for fixed columns (A-I) across all header rows
                $sheet->getStyle('A1:I4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '1F2937'], // Dark gray text
                        'name' => 'Segoe UI'
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB'] // Light gray background for fixed columns
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '9CA3AF'] // Gray border
                        ],
                    ],
                ]);

                // Style total column (only in last sheet)
                if ($this->isLastSheet) {
                    $totalColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                    $sheet->getStyle($totalColLetter . '1:' . $totalColLetter . '3')->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                            'color' => ['rgb' => '1F2937'], // Dark gray text
                            'name' => 'Segoe UI'
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D1D5DB'] // Gray background
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '9CA3AF'] // Gray border
                            ],
                        ],
                    ]);
                }

                // Don't use auto-size as it can cause performance issues with large sheets

                // Set minimum widths for specific columns
                $sheet->getColumnDimension('A')->setWidth(15); // Provinsi
                $sheet->getColumnDimension('B')->setWidth(10); // Kode Prov
                $sheet->getColumnDimension('C')->setWidth(20); // Kab/Kota
                $sheet->getColumnDimension('D')->setWidth(10); // Kode Kab
                $sheet->getColumnDimension('E')->setWidth(12); // Jenis
                $sheet->getColumnDimension('F')->setWidth(8);  // No. Partai
                $sheet->getColumnDimension('G')->setWidth(25); // Nama Partai/Caleg
                $sheet->getColumnDimension('H')->setWidth(8);  // No. Urut
                $sheet->getColumnDimension('I')->setWidth(15); // Dapil

                // Set row heights for headers
                $sheet->getRowDimension(1)->setRowHeight(25); // Kecamatan header
                $sheet->getRowDimension(2)->setRowHeight(20); // Kode kec header
                $sheet->getRowDimension(3)->setRowHeight(20); // Kode desa header
                $sheet->getRowDimension(4)->setRowHeight(20); // Desa name header

                // Freeze panes at row 5, column J (after fixed columns and headers)
                $sheet->freezePane('J5');

                // Add print settings for professional output
                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0); // Allow multiple pages vertically

                // Set margins for better printing
                $sheet->getPageMargins()
                    ->setTop(0.5)
                    ->setRight(0.3)
                    ->setLeft(0.3)
                    ->setBottom(0.5);

                // Add header and footer for print
                $sheet->getHeaderFooter()
                    ->setOddHeader('&C&B' . $this->sheetTitle)
                    ->setOddFooter('&L&D &T&R&P / &N'); // Date, Time, Page numbers
            }
        ];
    }
}