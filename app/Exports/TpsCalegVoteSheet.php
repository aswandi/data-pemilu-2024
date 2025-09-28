<?php

namespace App\Exports;

use App\Models\VoteData;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TpsCalegVoteSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $kecamatanId;
    protected $kecamatanName;
    protected $kabupatenName;
    protected $provinceName;
    protected $parties;
    protected $calegData;
    protected $dapilId;
    protected $headerStructure;

    public function __construct($kecamatanId, $kecamatanName, $kabupatenName, $provinceName)
    {
        $this->kecamatanId = $kecamatanId;
        $this->kecamatanName = $kecamatanName;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;

        // Get dapil_id for this kecamatan
        $kecamatanData = DB::table('pdpr_wil_kec')
            ->where('id', $this->kecamatanId)
            ->first();

        $this->dapilId = $kecamatanData ? $kecamatanData->dapil_id : null;

        // Get party data
        $this->parties = VoteData::getPartyData();

        // Get candidate data filtered by dapil_id
        if ($this->dapilId) {
            $this->calegData = DB::table('dpr_ri_caleg')
                ->where('dapil_id', $this->dapilId)
                ->orderBy('partai_id')
                ->orderBy('nomor_urut')
                ->get();
        } else {
            $this->calegData = collect();
        }

        // Build header structure for 3-row headers
        $this->buildHeaderStructure();
    }

    private function buildHeaderStructure()
    {
        $this->headerStructure = [
            'basic_columns' => ['No', 'Kelurahan/Desa', 'No TPS', 'Nama TPS', 'Total DPT'],
            'caleg_columns' => [],
            'summary_columns' => ['Total Suara Caleg', 'Partisipasi (%)']
        ];

        // Build caleg columns structure - show ALL candidates in dapil
        foreach ($this->parties as $party) {
            $partyCalegs = $this->calegData->where('partai_id', $party->nomor_urut); // Remove take(3) to show all
            foreach ($partyCalegs as $caleg) {
                $this->headerStructure['caleg_columns'][] = [
                    'party_name' => $party->partai_singkat ?? substr($party->nama, 0, 10),
                    'nomor_urut' => $caleg->nomor_urut,
                    'nama_caleg' => $caleg->nama
                ];
            }
        }
    }

    public function collection()
    {
        // Get TPS data with vote information for this kecamatan
        $tpsData = VoteData::getTpsDataWithVotesKecamatan($this->kecamatanId);
        return collect($tpsData);
    }

    public function headings(): array
    {
        // Create headers with 3-row information in single cells
        $headers = [
            'No',
            'Kelurahan/Desa',
            'No TPS',
            'Nama TPS',
            'Total DPT'
        ];

        // Add caleg columns with party/nomor/nama format - show ALL candidates
        foreach ($this->parties as $party) {
            $partyCalegs = $this->calegData->where('partai_id', $party->nomor_urut); // Show all candidates
            $partyName = $party->partai_singkat ?? substr($party->nama, 0, 8);

            foreach ($partyCalegs as $caleg) {
                // Format: Party Name\nNomor Urut\nNama Caleg
                $headers[] = "{$partyName}\n{$caleg->nomor_urut}\n{$caleg->nama}";
            }
        }

        $headers = array_merge($headers, [
            'Total Suara Caleg',
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

        // Parse candidate vote data from tbl column
        $tblData = json_decode($tps->caleg_vote_data, true) ?? [];
        $tpsCode = $this->getTpsCode($tps);
        $tpsVotes = isset($tblData[$tpsCode]) ? $tblData[$tpsCode] : [];
        $totalCalegVotes = 0;

        // Add ALL candidate vote columns for each party - filtered by dapil, show 0 if no votes
        foreach ($this->parties as $party) {
            $partyCalegs = $this->calegData->where('partai_id', $party->nomor_urut); // Show all candidates
            foreach ($partyCalegs as $caleg) {
                $suaraCaleg = isset($tpsVotes[$caleg->id]) ? intval($tpsVotes[$caleg->id]) : 0;
                $row[] = $suaraCaleg; // Always show number, including 0
            }
        }

        // Calculate total caleg votes from candidates in this dapil only
        $dapilCalegIds = $this->calegData->pluck('id')->toArray();
        foreach ($tpsVotes as $calegId => $votes) {
            if ($calegId !== 'null' && is_numeric($calegId) && is_numeric($votes) && in_array($calegId, $dapilCalegIds)) {
                $totalCalegVotes += intval($votes);
            }
        }

        // Add summary columns
        $totalDpt = intval($tps->total_dpt ?? 0);
        $partisipasi = $totalDpt > 0 ? round(($totalCalegVotes / $totalDpt) * 100, 2) : 0;

        $row = array_merge($row, [
            $totalCalegVotes,
            $partisipasi . '%'
        ]);

        return $row;
    }

    private function getTpsCode($tps)
    {
        // Generate TPS code using kel_kode + tps_number
        // The tbl data uses a format like "1671151001001" which is kel_kode + tps_number_padded
        $kelKode = $tps->kel_kode ?? '';
        $tpsNumber = '001'; // Default

        if (!empty($tps->no_tps)) {
            $tpsNumber = str_pad($tps->no_tps, 3, '0', STR_PAD_LEFT);
        } else if (!empty($tps->tps_nama)) {
            // Extract number from TPS name
            preg_match('/\d+/', $tps->tps_nama, $matches);
            if (!empty($matches)) {
                $tpsNumber = str_pad($matches[0], 3, '0', STR_PAD_LEFT);
            }
        }

        return $kelKode . $tpsNumber;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 9
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'E8F5E8']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
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

    public function title(): string
    {
        return 'Data Suara Caleg per TPS - Sesuai Dapil';
    }


}