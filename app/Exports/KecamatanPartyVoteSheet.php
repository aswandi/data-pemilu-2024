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

class KecamatanPartyVoteSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $kecamatanId;
    protected $kecamatanName;
    protected $kabupatenName;
    protected $provinceName;

    public function __construct($kecamatanId, $kecamatanName, $kabupatenName, $provinceName)
    {
        $this->kecamatanId = $kecamatanId;
        $this->kecamatanName = $kecamatanName;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;
    }

    public function collection()
    {
        // Get all kelurahan in this kecamatan
        $kelurahanData = \App\Models\Province::getKelurahanDataWithStats($this->kecamatanId);

        $kelurahanVoteData = collect();
        foreach($kelurahanData as $kelurahan) {
            $voteData = VoteData::getVoteDataByKelurahan($kelurahan->id);
            if (!empty($voteData)) {
                $voteRecord = $voteData[0]; // Get first record
                $voteRecord->nama_kelurahan = $kelurahan->nama_kelurahan;
                $voteRecord->kode_kelurahan = $kelurahan->id; // Use ID as code for now
                $kelurahanVoteData->push($voteRecord);
            }
        }

        return $kelurahanVoteData;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Kelurahan/Desa',
            'Kode Kelurahan',
            'Suara Partai 1 (PKB)',
            'Suara Partai 2 (Gerindra)',
            'Suara Partai 3 (PDIP)',
            'Suara Partai 4 (Golkar)',
            'Suara Partai 5 (NasDem)',
            'Suara Partai 6 (Buruh)',
            'Suara Partai 7 (Gelora)',
            'Suara Partai 8 (PKS)',
            'Suara Partai 9 (PKN)',
            'Suara Partai 10 (Hanura)',
            'Suara Partai 11 (Garuda)',
            'Suara Partai 12 (PAN)',
            'Suara Partai 13 (PBB)',
            'Suara Partai 14 (Demokrat)',
            'Suara Partai 15 (PSI)',
            'Suara Partai 16 (Perindo)',
            'Suara Partai 17 (PPP)',
            'Suara Partai 18 (Ummat)',
            'Total Suara Partai',
            'Total DPT'
        ];
    }

    public function map($vote): array
    {
        static $counter = 0;
        $counter++;

        $chart = json_decode($vote->chart, true) ?? [];
        $partaiVotes = [];
        $totalPartai = 0;

        for ($i = 1; $i <= 18; $i++) {
            $suara = isset($chart['partai'][$i]) ? intval($chart['partai'][$i]) : 0;
            $partaiVotes[] = $suara;
            $totalPartai += $suara;
        }

        return array_merge([
            $counter,
            $vote->nama_kelurahan,
            $vote->kode_kelurahan,
        ], $partaiVotes, [
            $totalPartai,
            $vote->total_dpt ?? 0
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'E3F2FD']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            'A:Z' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            'B:B' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];
    }

    public function title(): string
    {
        return 'Data Suara Partai';
    }
}