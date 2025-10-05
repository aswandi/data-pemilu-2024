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

class KabupatenCalegExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;
    protected $kelurahanVoteData;
    protected $parties;
    protected $calegWithVotes;
    protected $partySummary;

    public function __construct($kabupatenId, $kabupatenName, $provinceName)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;

        $this->parties = VoteData::getPartyData();
        $this->loadKelurahanVoteData();
        $this->loadCalegWithVotes();
        $this->calculatePartySummary();
    }

    protected function loadKelurahanVoteData()
    {
        // Limit to first 20 kelurahan to prevent timeout
        // Get all kecamatan in this kabupaten
        $kecamatanData = Province::getKecamatanDataWithStats($this->kabupatenId);

        $this->kelurahanVoteData = collect();
        $kelurahanCount = 0;
        $maxKelurahan = 20; // Limit to prevent timeout

        foreach($kecamatanData as $kecamatan) {
            if ($kelurahanCount >= $maxKelurahan) {
                break;
            }

            // Get all kelurahan in this kecamatan
            $kelurahanData = Province::getKelurahanDataWithStats($kecamatan->id);

            foreach($kelurahanData as $kelurahan) {
                if ($kelurahanCount >= $maxKelurahan) {
                    break;
                }

                $voteData = VoteData::getVoteDataByKelurahan($kelurahan->id);
                if (!empty($voteData)) {
                    $this->kelurahanVoteData->push([
                        'kelurahan_info' => $kelurahan,
                        'kecamatan_info' => $kecamatan,
                        'vote_data' => $voteData[0]
                    ]);
                    $kelurahanCount++;
                }
            }
        }
    }

    protected function loadCalegWithVotes()
    {
        // Get all caleg data
        $calegData = VoteData::getCalegData();

        $this->calegWithVotes = collect();

        foreach($calegData as $caleg) {
            $totalSuara = 0;

            // Calculate total votes for this caleg across all kelurahan
            foreach($this->kelurahanVoteData as $kelData) {
                $tbl = json_decode($kelData['vote_data']->tbl, true) ?? [];

                // Loop through each TPS in this kelurahan
                foreach($tbl as $tpsId => $tpsData) {
                    if (isset($tpsData[$caleg->id])) {
                        $totalSuara += intval($tpsData[$caleg->id]);
                    }
                }
            }

            // Only include caleg with votes > 0
            if ($totalSuara > 0) {
                $caleg->total_suara = $totalSuara;
                $this->calegWithVotes->push($caleg);
            }

            // Limit candidates to prevent timeout
            if ($this->calegWithVotes->count() >= 100) {
                break;
            }
        }

        // Sort by party number first, then by caleg number
        $this->calegWithVotes = $this->calegWithVotes->sort(function($a, $b) {
            // First sort by party ID (nomor urut partai)
            if ($a->partai_id !== $b->partai_id) {
                return $a->partai_id - $b->partai_id;
            }
            // Then sort by caleg nomor urut within the same party
            return $a->nomor_urut - $b->nomor_urut;
        });
    }

    protected function calculatePartySummary()
    {
        $this->partySummary = collect();

        // Group caleg by party and calculate totals
        $groupedByParty = $this->calegWithVotes->groupBy('partai_id');

        foreach($groupedByParty as $partaiId => $calegs) {
            $partyTotalVotes = 0;
            $partyVotesByKelurahan = [];

            // Calculate total for this party across all kelurahan
            foreach($this->kelurahanVoteData as $kelIndex => $kelData) {
                $chart = json_decode($kelData['vote_data']->chart, true) ?? [];
                $tbl = json_decode($kelData['vote_data']->tbl, true) ?? [];

                // Get party vote from chart
                $partyVotes = isset($chart[$partaiId]) ? intval($chart[$partaiId]) : 0;

                // Get candidate votes from tbl for this party
                $calegVotes = 0;
                foreach($calegs as $caleg) {
                    foreach($tbl as $tpsId => $tpsData) {
                        if (isset($tpsData[$caleg->id])) {
                            $calegVotes += intval($tpsData[$caleg->id]);
                        }
                    }
                }

                $totalKelurahanVotes = $partyVotes + $calegVotes;
                $partyVotesByKelurahan[] = $totalKelurahanVotes;
                $partyTotalVotes += $totalKelurahanVotes;
            }

            $this->partySummary->push([
                'partai_id' => $partaiId,
                'total_votes' => $partyTotalVotes,
                'votes_by_kelurahan' => $partyVotesByKelurahan
            ]);
        }
    }

    public function collection()
    {
        $result = collect();
        $groupedByParty = $this->calegWithVotes->groupBy('partai_id');

        foreach($groupedByParty as $partaiId => $calegs) {
            // Add party total row first
            $partySummary = $this->partySummary->where('partai_id', $partaiId)->first();
            if ($partySummary) {
                $partyRow = (object)[
                    'is_party_total' => true,
                    'partai_id' => $partaiId,
                    'total_votes' => $partySummary['total_votes'],
                    'votes_by_kelurahan' => $partySummary['votes_by_kelurahan']
                ];
                $result->push($partyRow);
            }

            // Add all candidates for this party
            foreach($calegs as $caleg) {
                $result->push($caleg);
            }
        }

        return $result;
    }

    public function headings(): array
    {
        $headings = [
            'No',
            'No. Urut Partai',
            'Partai',
            'No. Urut',
            'Nama Caleg',
            'L/P',
            'Total Suara'
        ];

        // Add kelurahan columns
        foreach($this->kelurahanVoteData as $kelData) {
            $kelurahanName = $kelData['kelurahan_info']->nama_kelurahan;
            $kecamatanName = $kelData['kecamatan_info']->nama_kecamatan;
            $headings[] = $kelurahanName . "\n(" . $kecamatanName . ")";
        }

        return $headings;
    }

    public function map($item): array
    {
        static $counter = 0;

        // Check if this is a party total row
        if (isset($item->is_party_total) && $item->is_party_total) {
            $row = [
                '',
                $item->partai_id,
                'TOTAL ' . $this->getPartaiName($item->partai_id),
                '',
                '',
                '',
                number_format($item->total_votes, 0, ',', '.')
            ];

            // Add party vote totals for each kelurahan
            foreach($item->votes_by_kelurahan as $kelurahanVotes) {
                $row[] = number_format($kelurahanVotes, 0, ',', '.');
            }

            return $row;
        }

        // This is a regular candidate row
        $counter++;
        $caleg = $item;

        $row = [
            $counter,
            $caleg->partai_id,
            $this->getPartaiName($caleg->partai_id),
            $caleg->nomor_urut,
            $caleg->nama,
            $caleg->jenis_kelamin,
            number_format($caleg->total_suara, 0, ',', '.')
        ];

        // Add vote data for each kelurahan
        foreach($this->kelurahanVoteData as $kelData) {
            $tbl = json_decode($kelData['vote_data']->tbl, true) ?? [];
            $suara = 0;

            // Sum votes across all TPS in this kelurahan
            foreach($tbl as $tpsId => $tpsData) {
                if (isset($tpsData[$caleg->id])) {
                    $suara += intval($tpsData[$caleg->id]);
                }
            }

            $row[] = number_format($suara, 0, ',', '.');
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
        // Calculate total rows: header + candidates + party total rows
        $partyCount = $this->calegWithVotes->groupBy('partai_id')->count();
        $totalDataRows = $this->calegWithVotes->count() + $partyCount;
        $lastRow = $totalDataRows + 1; // +1 for header
        $lastColumn = chr(71 + $this->kelurahanVoteData->count()); // G + number of kelurahan

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
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
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
            "C2:C{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ],
            "E2:E{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];

        // Add styling for party total rows (bold and highlighted)
        $currentRow = 2; // Start from row 2 (after header)
        $groupedByParty = $this->calegWithVotes->groupBy('partai_id');

        foreach($groupedByParty as $partaiId => $calegs) {
            // Style the party total row
            $styles["A{$currentRow}:{$lastColumn}{$currentRow}"] = [
                'font' => [
                    'bold' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F3F4F6']
                ]
            ];
            $currentRow++; // Move to next row after party total
            $currentRow += $calegs->count(); // Skip candidate rows
        }

        return $styles;
    }

    public function title(): string
    {
        return 'Data Suara Caleg per Desa - ' . $this->kabupatenName;
    }
}