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

class KabupatenCalegFromKecamatanSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;
    protected $desaVoteData;
    protected $parties;
    protected $calegWithVotes;
    protected $exportData;
    protected $dapilInfo;
    protected $sheetTitle;
    protected $isLastSheet;
    protected $votesByDesa;

    public function __construct($kabupatenId, $kabupatenName, $provinceName, $desaChunk, $sheetTitle, $isLastSheet = true)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;
        $this->desaVoteData = $desaChunk;
        $this->sheetTitle = $sheetTitle;
        $this->isLastSheet = $isLastSheet;

        $this->parties = VoteData::getPartyData();

        // Get dapil from hr_dpr_ri_kec
        $this->dapilInfo = VoteData::getDapilByKabupatenFromHR($this->kabupatenId);

        // Fallback to old method if no data
        if (empty($this->dapilInfo)) {
            $this->dapilInfo = VoteData::getDapilByKabupaten($this->kabupatenId);
        }

        $this->loadCalegWithVotes();
        $this->prepareExportData();
    }

    protected function loadCalegWithVotes()
    {
        // Get dapil IDs from dapil info
        $dapilIds = array_column($this->dapilInfo, 'dapil_id');

        // Get caleg data filtered by dapil
        if (!empty($dapilIds)) {
            $calegData = VoteData::getCalegDataByDapil($dapilIds);
        } else {
            // Fallback to all caleg if no dapil found
            $calegData = VoteData::getCalegData();
        }

        $this->calegWithVotes = collect();

        // Pre-process vote data from hr_dpr_ri_kec for each desa
        $this->votesByDesa = [];
        foreach($this->desaVoteData as $desaIndex => $desaData) {
            $votes = $desaData['vote_data']->votes ?? [];
            $this->votesByDesa[$desaIndex] = $votes;
        }

        $maxCaleg = 200; // Show more candidates
        $calegCount = 0;

        foreach($calegData as $caleg) {
            if ($calegCount >= $maxCaleg) {
                break;
            }

            $totalSuara = 0;

            // Calculate total votes for this caleg across all desa
            foreach($this->votesByDesa as $desaVotes) {
                if (isset($desaVotes[$caleg->id])) {
                    $totalSuara += intval($desaVotes[$caleg->id]);
                }
            }

            // Include caleg with any votes or first 100 regardless
            if ($totalSuara > 0 || $calegCount < 100) {
                $caleg->total_suara = $totalSuara;
                $this->calegWithVotes->push($caleg);
                $calegCount++;
            }

            // Memory cleanup
            if ($calegCount % 50 === 0) {
                gc_collect_cycles();
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

        // Group candidates by party
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

            // Add party-only votes row
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

        // Add grand total row
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

        // Add desa/kelurahan columns
        foreach($this->desaVoteData as $desaData) {
            $headings[] = $desaData['kelurahan_info']->nama_kelurahan;
        }

        // Add total column in last sheet
        if ($this->isLastSheet) {
            $headings[] = 'Total Suara';
        }

        return $headings;
    }

    public function map($item): array
    {
        // Get regional info from first desa
        $firstDesa = $this->desaVoteData->first();

        $row = [
            $firstDesa['provinsi_info']->nama_provinsi ?? '',
            $firstDesa['provinsi_info']->pro_kode ?? '',
            $firstDesa['kabupaten_info']->nama_kabupaten ?? '',
            $firstDesa['kabupaten_info']->kab_kode ?? '',
            $item['type'] === 'party' ? 'PARTAI' : ($item['type'] === 'party_only' ? 'PARTAI SAJA' : ($item['type'] === 'total_row' ? 'TOTAL' : 'CALEG')),
            $item['partai_id'],
            $item['name'],
            $item['nomor_urut'],
            $item['dapil_nama'] ?? ''
        ];

        $totalVotes = 0;

        // Add vote data for each desa
        foreach($this->desaVoteData as $desaIndex => $desaData) {
            $votes = null;
            $hasData = $desaData['vote_data']->has_data ?? false;
            $desaVotes = $desaData['vote_data']->votes ?? [];
            $chart = $desaData['vote_data']->chart ?? [];

            if ($item['type'] === 'total_row') {
                // Grand total: sum all votes for this desa
                $totalDesaVotes = 0;

                // Sum party-only votes from chart
                if (is_array($chart)) {
                    foreach($chart as $partyData) {
                        if (is_array($partyData) && isset($partyData['jml_suara_partai'])) {
                            $totalDesaVotes += intval($partyData['jml_suara_partai']);
                        }
                    }
                }

                // Sum all candidate votes
                if (is_array($desaVotes)) {
                    foreach($desaVotes as $calegVotes) {
                        $totalDesaVotes += intval($calegVotes);
                    }
                }

                $votes = $totalDesaVotes;

            } elseif ($item['type'] === 'party') {
                // Party total: party-only + all caleg votes for this party
                $partyOnlyVotes = 0;
                if (is_array($chart) && isset($chart[$item['partai_id']]['jml_suara_partai'])) {
                    $partyOnlyVotes = intval($chart[$item['partai_id']]['jml_suara_partai']);
                }

                // Get caleg votes for this party
                $partyCandidateVotes = 0;
                $partyCalegIds = $this->calegWithVotes->where('partai_id', $item['partai_id'])->pluck('id')->toArray();

                if (is_array($desaVotes)) {
                    foreach($partyCalegIds as $calegId) {
                        if (isset($desaVotes[$calegId])) {
                            $partyCandidateVotes += intval($desaVotes[$calegId]);
                        }
                    }
                }

                $votes = $partyOnlyVotes + $partyCandidateVotes;

            } elseif ($item['type'] === 'party_only') {
                // Party-only votes from chart
                $partyOnlyVotes = 0;
                if (is_array($chart) && isset($chart[$item['partai_id']]['jml_suara_partai'])) {
                    $partyOnlyVotes = intval($chart[$item['partai_id']]['jml_suara_partai']);
                }
                $votes = $partyOnlyVotes;

            } else {
                // Caleg votes
                $candidateVotes = 0;
                if (is_array($desaVotes) && isset($desaVotes[$item['id']])) {
                    $candidateVotes = intval($desaVotes[$item['id']]);
                    $votes = $candidateVotes;
                }
            }

            // Display logic
            if (!$hasData || $votes === null) {
                $row[] = '-';
            } else {
                $row[] = number_format($votes, 0, ',', '.');
                $totalVotes += $votes;
            }
        }

        // Add total column in last sheet
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

        // Calculate last column
        if ($this->isLastSheet) {
            $lastColumn = chr(73 + $this->desaVoteData->count()); // J + desa count + total
        } else {
            $lastColumn = chr(72 + $this->desaVoteData->count()); // I + desa count
        }

        $styles = [
            // All data rows with borders
            "A5:{$lastColumn}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB']
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
            // Left align names
            "G4:G{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'font' => [
                    'size' => 10
                ]
            ],
            // Left align dapil names
            "I4:I{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'font' => [
                    'size' => 9,
                    'color' => ['rgb' => '6B7280']
                ]
            ],
            // Right align vote columns
            "J4:{$lastColumn}{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'font' => [
                    'size' => 10
                ]
            ]
        ];

        return $styles;
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

                // Insert 3 rows for kode headers
                $sheet->insertNewRowBefore(1, 3);

                // Row 1: Kecamatan names
                // Row 2: Kode Kecamatan
                // Row 3: Kode Desa
                // Row 4: Nama Desa (original headings)

                // Merge fixed header columns (A-I) across rows 1-3
                $sheet->mergeCells('A1:A3');
                $sheet->mergeCells('B1:B3');
                $sheet->mergeCells('C1:C3');
                $sheet->mergeCells('D1:D3');
                $sheet->mergeCells('E1:E3');
                $sheet->mergeCells('F1:F3');
                $sheet->mergeCells('G1:G3');
                $sheet->mergeCells('H1:H3');
                $sheet->mergeCells('I1:I3');

                // Add kode headers for desa/kelurahan columns
                $currentCol = 10; // Start at column J

                // Group desa by kecamatan for header merging
                $groupedByKecamatan = $this->desaVoteData->groupBy('kecamatan_info.nama_kecamatan');

                foreach($groupedByKecamatan as $kecamatanName => $desaInKecamatan) {
                    $desaCount = $desaInKecamatan->count();
                    $startCol = $currentCol;

                    // Add Row 1: Kecamatan name (merged if multiple desa)
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                    $sheet->setCellValue($colLetter . '1', $kecamatanName);

                    if ($desaCount > 1) {
                        $endColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol + $desaCount - 1);
                        $sheet->mergeCells($colLetter . '1:' . $endColLetter . '1');
                    }

                    // Add Row 2: Kode Kecamatan (merged if multiple desa)
                    $kecKode = $desaInKecamatan->first()['kecamatan_info']->kec_kode ?? '';
                    $sheet->setCellValue($colLetter . '2', $kecKode);

                    if ($desaCount > 1) {
                        $endColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol + $desaCount - 1);
                        $sheet->mergeCells($colLetter . '2:' . $endColLetter . '2');
                    }

                    // Add Row 3: Kode Desa for each desa
                    foreach($desaInKecamatan as $desaData) {
                        $desaColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                        $kelKode = $desaData['kelurahan_info']->kel_kode ?? '';
                        $sheet->setCellValue($desaColLetter . '3', $kelKode);
                        $currentCol++;
                    }
                }

                // Add header for Total column in last sheet
                if ($this->isLastSheet) {
                    $totalColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                    $sheet->setCellValue($totalColLetter . '1', 'Total Suara');
                    $sheet->mergeCells($totalColLetter . '1:' . $totalColLetter . '3');
                    $lastColumn = $totalColLetter;
                } else {
                    $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol - 1);
                }

                // Style row 1 (Kecamatan names)
                $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '374151'],
                        'name' => 'Segoe UI'
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '9CA3AF']
                        ],
                    ],
                ]);

                // Style row 2 (Kode Kecamatan)
                $sheet->getStyle('A2:' . $lastColumn . '2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '374151'],
                        'name' => 'Segoe UI'
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '9CA3AF']
                        ],
                    ],
                ]);

                // Style row 3 (Kode Desa)
                $sheet->getStyle('A3:' . $lastColumn . '3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '374151'],
                        'name' => 'Segoe UI'
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '9CA3AF']
                        ],
                    ],
                ]);

                // Style row 4 (Nama Desa)
                $sheet->getStyle('A4:' . $lastColumn . '4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '1F2937'],
                        'name' => 'Segoe UI'
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '9CA3AF']
                        ],
                    ],
                ]);

                // Add background color for PARTAI rows (party total rows)
                $currentRow = 5; // Start from row 5 (after 4 header rows)
                foreach($this->exportData as $item) {
                    if ($item['type'] === 'party') {
                        // Apply blue background color for party total rows
                        $sheet->getStyle('A' . $currentRow . ':' . $lastColumn . $currentRow)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'DBEAFE'] // Light blue background
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => '1E40AF'] // Dark blue text
                            ]
                        ]);
                    } elseif ($item['type'] === 'total_row') {
                        // Apply darker background for grand total row
                        $sheet->getStyle('A' . $currentRow . ':' . $lastColumn . $currentRow)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FEF3C7'] // Light yellow background
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => '92400E'] // Dark yellow/brown text
                            ]
                        ]);
                    }
                    $currentRow++;
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(15); // Provinsi
                $sheet->getColumnDimension('B')->setWidth(10); // Kode Prov
                $sheet->getColumnDimension('C')->setWidth(20); // Kab/Kota
                $sheet->getColumnDimension('D')->setWidth(10); // Kode Kab
                $sheet->getColumnDimension('E')->setWidth(12); // Jenis
                $sheet->getColumnDimension('F')->setWidth(8);  // No. Partai
                $sheet->getColumnDimension('G')->setWidth(30); // Nama Partai/Caleg
                $sheet->getColumnDimension('H')->setWidth(8);  // No. Urut
                $sheet->getColumnDimension('I')->setWidth(18); // Dapil

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(25); // Kecamatan header
                $sheet->getRowDimension(2)->setRowHeight(20); // Kode kec header
                $sheet->getRowDimension(3)->setRowHeight(20); // Kode desa header
                $sheet->getRowDimension(4)->setRowHeight(20); // Nama desa header

                // Freeze panes at row 5, column J
                $sheet->freezePane('J5');

                // Print settings
                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                // Margins
                $sheet->getPageMargins()
                    ->setTop(0.5)
                    ->setRight(0.3)
                    ->setLeft(0.3)
                    ->setBottom(0.5);

                // Header and footer
                $sheet->getHeaderFooter()
                    ->setOddHeader('&C&B' . $this->sheetTitle)
                    ->setOddFooter('&L&D &T&R&P / &N');
            }
        ];
    }
}
