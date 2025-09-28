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

class KecamatanCalegWebSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $kecamatanId;
    protected $kecamatanName;
    protected $kabupatenName;
    protected $provinceName;
    protected $kelurahanVoteData;
    protected $parties;
    protected $calegWithVotes;

    public function __construct($kecamatanId, $kecamatanName, $kabupatenName, $provinceName)
    {
        $this->kecamatanId = $kecamatanId;
        $this->kecamatanName = $kecamatanName;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;

        $this->parties = VoteData::getPartyData();
        $this->loadKelurahanVoteData();
        $this->loadCalegWithVotes();
    }

    protected function loadKelurahanVoteData()
    {
        // Get all kelurahan in this kecamatan - same as web
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

            // Only include caleg with votes > 0 (as shown in web)
            if ($totalSuara > 0) {
                $caleg->total_suara = $totalSuara;
                $this->calegWithVotes->push($caleg);
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

    public function collection()
    {
        return $this->calegWithVotes;
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
            $headings[] = $kelData['kelurahan_info']->nama_kelurahan;
        }

        return $headings;
    }

    public function map($caleg): array
    {
        static $counter = 0;
        $counter++;

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
        $lastRow = $this->calegWithVotes->count() + 1; // +1 for header
        $lastColumn = chr(71 + $this->kelurahanVoteData->count()); // G + number of kelurahan (updated for new column)

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
    }

    public function title(): string
    {
        return 'Data Suara Caleg per Kelurahan';
    }
}