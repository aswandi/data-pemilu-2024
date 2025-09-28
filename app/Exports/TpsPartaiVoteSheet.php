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

class TpsPartaiVoteSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
        // Get TPS data with vote information for this kecamatan
        $tpsData = VoteData::getTpsDataWithVotesKecamatan($this->kecamatanId);
        return collect($tpsData);
    }

    public function headings(): array
    {
        return [
            'No',
            'Kelurahan/Desa',
            'No TPS',
            'Nama TPS',
            'Total DPT',
            'PKB (1)',
            'Gerindra (2)',
            'PDIP (3)',
            'Golkar (4)',
            'NasDem (5)',
            'Buruh (6)',
            'Gelora (7)',
            'PKS (8)',
            'PKN (9)',
            'Hanura (10)',
            'Garuda (11)',
            'PAN (12)',
            'PBB (13)',
            'Demokrat (14)',
            'PSI (15)',
            'Perindo (16)',
            'PPP (17)',
            'Ummat (18)',
            'Total Suara Partai'
        ];
    }

    public function map($tps): array
    {
        static $counter = 0;
        $counter++;

        // Parse party vote data from chart column
        $chart = json_decode($tps->party_vote_data, true) ?? [];
        $partaiVotes = [];
        $totalPartai = 0;

        // Get votes for each party (1-18)
        for ($i = 1; $i <= 18; $i++) {
            $suara = isset($chart[$i]['jml_suara_partai']) ? intval($chart[$i]['jml_suara_partai']) : 0;
            $partaiVotes[] = $suara;
            $totalPartai += $suara;
        }

        return array_merge([
            $counter,
            $tps->kelurahan_nama,
            $tps->no_tps,
            $tps->tps_nama,
            $tps->total_dpt ?? 0,
        ], $partaiVotes, [
            $totalPartai
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
            'B:D' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];
    }

    public function title(): string
    {
        return 'Data Suara Partai per TPS';
    }
}