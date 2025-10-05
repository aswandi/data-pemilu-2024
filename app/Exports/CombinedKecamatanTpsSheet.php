<?php

namespace App\Exports;

use App\Models\VoteData;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CombinedKecamatanTpsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnFormatting
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;
    protected $parties;
    protected $allTpsData;
    protected $kecamatanList;

    public function __construct($kabupatenId, $kabupatenName, $provinceName)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;
        $this->parties = VoteData::getPartyData();

        // Get all kecamatan in this kabupaten
        $this->kecamatanList = Province::getKecamatanDataWithStats($kabupatenId);

        // Collect all TPS data from all kecamatan
        $this->allTpsData = collect();
        foreach ($this->kecamatanList as $kecamatan) {
            $tpsData = VoteData::getTpsDataWithVotesKecamatan($kecamatan->id);
            foreach ($tpsData as $tps) {
                // kecamatan_nama already included from query
                $this->allTpsData->push($tps);
            }
        }
    }

    public function collection()
    {
        return $this->allTpsData;
    }

    public function headings(): array
    {
        $headers = [
            'No',
            'Provinsi',
            'Kode Prov',
            'Kab/Kota',
            'Kode Kab',
            'Kecamatan',
            'Kode Kec',
            'Kelurahan/Desa',
            'Kode Desa',
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
            $tps->provinsi_nama ?? '',
            $tps->pro_kode ?? '',
            $tps->kabupaten_nama ?? '',
            $tps->kab_kode ?? '',
            $tps->kecamatan_nama,
            $tps->kec_kode ?? '',
            $tps->kelurahan_nama,
            $tps->kel_kode ?? '',
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
                $suaraPartai = intval($chartData[$party->nomor_urut]['jml_suara_total']); // Show actual value including 0
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
            'B:B' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ],
            'D:D' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ],
            'F:F' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ],
            'H:H' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ],
            'K:K' => [
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
            'J' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
        ];

        // Format party vote columns to show zeros
        $startColIndex = 13; // Column M (0-based: A=1, B=2, ..., M=13)
        for ($i = 0; $i < count($this->parties); $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColIndex + $i);
            $formats[$col] = '0;-0;0;@'; // Custom format: positive;negative;zero;text
        }

        // Format total vote and participation columns
        $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColIndex + count($this->parties));
        $partisipasiCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColIndex + count($this->parties) + 1);
        $formats[$totalCol] = '0;-0;0;@';
        $formats[$partisipasiCol] = '@'; // Text format for percentage

        return $formats;
    }

    public function title(): string
    {
        return 'Semua TPS ' . $this->kabupatenName;
    }
}