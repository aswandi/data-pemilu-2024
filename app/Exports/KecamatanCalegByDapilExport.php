<?php

namespace App\Exports;

use App\Models\VoteData;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class KecamatanCalegByDapilExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $kecamatanId;
    protected $kecamatanName;
    protected $kabupatenName;
    protected $provinceName;
    protected $dapilId;
    protected $candidates;
    protected $voteData;

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

        // Get candidates filtered by dapil_id
        if ($this->dapilId) {
            $this->candidates = DB::table('dpr_ri_caleg')
                ->where('dapil_id', $this->dapilId)
                ->orderBy('partai_id')
                ->orderBy('nomor_urut')
                ->get();
        } else {
            $this->candidates = collect();
        }

        // Get all kelurahan vote data for this kecamatan
        $kelurahanData = \App\Models\Province::getKelurahanDataWithStats($this->kecamatanId);
        $this->voteData = collect();
        foreach($kelurahanData as $kelurahan) {
            $voteData = VoteData::getVoteDataByKelurahan($kelurahan->id);
            if (!empty($voteData)) {
                $voteRecord = $voteData[0];
                $voteRecord->nama_kelurahan = $kelurahan->nama_kelurahan;
                $voteRecord->kelurahan_id = $kelurahan->id;
                $this->voteData->push($voteRecord);
            }
        }
    }

    public function collection()
    {
        $exportData = collect();

        foreach ($this->candidates as $candidate) {
            $candidateData = (object)[
                'candidate_id' => $candidate->id,
                'candidate_name' => $candidate->nama,
                'candidate_number' => $candidate->nomor_urut,
                'party_id' => $candidate->partai_id,
                'dapil_id' => $candidate->dapil_id,
                'dapil_nama' => $candidate->dapil_nama,
                'vote_data' => $this->voteData
            ];
            $exportData->push($candidateData);
        }

        return $exportData;
    }

    public function headings(): array
    {
        $baseHeadings = [
            'No',
            'Nama Caleg',
            'No. Urut',
            'Partai ID',
            'Dapil ID',
            'Nama Dapil'
        ];

        $kelurahanHeadings = [];
        foreach ($this->voteData as $vote) {
            $kelurahanHeadings[] = $vote->nama_kelurahan;
        }

        $kelurahanHeadings[] = 'Total Suara Caleg';

        return array_merge($baseHeadings, $kelurahanHeadings);
    }

    public function map($candidateData): array
    {
        static $counter = 0;
        $counter++;

        $row = [
            $counter,
            $candidateData->candidate_name,
            $candidateData->candidate_number,
            $candidateData->party_id,
            $candidateData->dapil_id,
            $candidateData->dapil_nama
        ];

        $totalCalegVotes = 0;

        foreach ($candidateData->vote_data as $vote) {
            $chart = json_decode($vote->chart, true) ?? [];
            $calegVotes = isset($chart['caleg'][$candidateData->candidate_id]) ?
                intval($chart['caleg'][$candidateData->candidate_id]) : 0;

            $row[] = $calegVotes;
            $totalCalegVotes += $calegVotes;
        }

        $row[] = $totalCalegVotes;

        return $row;
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
                    'startColor' => ['rgb' => 'E8F5E8']
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
            ],
            'F:F' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];
    }

    public function title(): string
    {
        return 'Data Caleg Per Dapil - ' . $this->kecamatanName;
    }
}