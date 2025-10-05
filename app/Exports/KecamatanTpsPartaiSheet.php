<?php

namespace App\Exports;

use App\Models\VoteData;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class KecamatanTpsPartaiSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnFormatting
{
    protected $kecamatanId;
    protected $kecamatanName;
    protected $parties;

    public function __construct($kecamatanId, $kecamatanName)
    {
        $this->kecamatanId = $kecamatanId;
        $this->kecamatanName = $kecamatanName;
        $this->parties = VoteData::getPartyData();
    }

    public function collection()
    {
        // Get TPS data with party vote information for this kecamatan
        $tpsData = VoteData::getTpsDataWithVotesKecamatan($this->kecamatanId);
        return collect($tpsData);
    }

    public function headings(): array
    {
        $headers = [
            'No',
            'Kelurahan/Desa',
            'No TPS',
            'Nama TPS',
            'Total DPT'
        ];

        // Add party vote columns
        foreach ($this->parties as $party) {
            $headers[] = $party->partai_singkat ?: substr($party->nama, 0, 10);
        }

        $headers = array_merge($headers, [
            'Total Suara Partai',
            'Partisipasi (%)'
        ]);

        return $headers;
    }

    public function map($tps): array
    {
        static $counter = 0;
        $counter++;

        $row = [
            $counter,
            $tps->kelurahan_nama,
            $tps->no_tps,
            $tps->tps_nama,
            intval($tps->total_dpt ?? 0)
        ];

        // Parse party vote data from chart column (from hs_dpr_ri_tps table)
        $chartData = null;
        if (!empty($tps->party_vote_data) && $tps->party_vote_data !== '""') {
            $chartData = json_decode($tps->party_vote_data, true);
        }

        $totalPartaiVotes = 0;

        // Add party vote columns
        foreach ($this->parties as $party) {
            if ($chartData && isset($chartData[$party->nomor_urut]['jml_suara_total'])) {
                $suaraPartai = intval($chartData[$party->nomor_urut]['jml_suara_total']); // Show actual value (including 0)
            } else {
                $suaraPartai = '-'; // Show dash if no data available
            }
            $row[] = $suaraPartai;
            if (is_numeric($suaraPartai)) {
                $totalPartaiVotes += $suaraPartai;
            }
        }

        // Add summary columns
        $totalDpt = intval($tps->total_dpt ?? 0);

        // Handle participation calculation - show dash if no vote data
        if ($totalPartaiVotes > 0 && $totalDpt > 0) {
            $partisipasi = round(($totalPartaiVotes / $totalDpt) * 100, 2) . '%';
        } else {
            $partisipasi = $chartData ? '0%' : '-'; // Dash if no data at all
        }

        $row = array_merge($row, [
            $totalPartaiVotes > 0 ? $totalPartaiVotes : ($chartData ? 0 : '-'),
            $partisipasi
        ]);

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 10
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
            'A:ZZ' => [
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

    public function columnFormats(): array
    {
        $formats = [
            'A' => NumberFormat::FORMAT_NUMBER,
            'C' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER,
        ];

        // Format party vote columns to show zeros
        $startCol = 'F'; // First party column (after No, Kelurahan, No TPS, Nama TPS, Total DPT)
        for ($i = 0; $i < count($this->parties); $i++) {
            $col = chr(ord($startCol) + $i);
            $formats[$col] = '0;-0;0;@'; // Custom format: positive;negative;zero;text
        }

        // Format total vote and participation columns
        $totalCol = chr(ord($startCol) + count($this->parties));
        $partisipasiCol = chr(ord($startCol) + count($this->parties) + 1);
        $formats[$totalCol] = '0;-0;0;@';
        $formats[$partisipasiCol] = '@'; // Text format for percentage

        return $formats;
    }

    public function title(): string
    {
        return 'TPS ' . $this->kecamatanName;
    }
}