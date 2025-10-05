<?php

namespace App\Exports;

use App\Models\VoteData;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KabupatenCalegFromKecamatanExport implements WithMultipleSheets
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;

    public function __construct($kabupatenId, $kabupatenName, $provinceName)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Get all desa/kelurahan data from hr_dpr_ri_kec
        $allDesaData = $this->getKecamatanData();

        // Split into chunks of 50 desa per sheet for better readability
        $desaChunks = $allDesaData->chunk(50);

        foreach($desaChunks as $index => $desaChunk) {
            $sheetNumber = $index + 1;
            $isLastSheet = ($index === $desaChunks->count() - 1);
            $sheetTitle = "Sheet {$sheetNumber}";

            if ($desaChunks->count() == 1) {
                // If only one sheet, use kabupaten name
                $sheetTitle = substr($this->kabupatenName, 0, 31); // Excel sheet name limit
            } else {
                // Multiple sheets, add sheet number
                $baseTitle = substr($this->kabupatenName, 0, 25); // Leave room for " - X"
                if ($isLastSheet) {
                    $sheetTitle = "{$baseTitle} - Total";
                } else {
                    $sheetTitle = "{$baseTitle} - {$sheetNumber}";
                }
            }

            $sheets[] = new KabupatenCalegFromKecamatanSheetExport(
                $this->kabupatenId,
                $this->kabupatenName,
                $this->provinceName,
                $desaChunk,
                $sheetTitle,
                $isLastSheet
            );
        }

        return $sheets;
    }

    /**
     * Get all desa/kelurahan data with votes from hr_dpr_ri_kec
     */
    private function getKecamatanData()
    {
        // First, get all desa/kelurahan in this kabupaten
        $allDesa = \DB::select("
            SELECT
                kel.id as kelurahan_id,
                kel.nama as kelurahan_nama,
                kel.kel_kode,
                kec.id as kecamatan_id,
                kec.nama as kecamatan_nama,
                kec.kec_kode,
                kab.id as kabupaten_id,
                kab.nama as kabupaten_nama,
                kab.kab_kode,
                pro.id as provinsi_id,
                pro.nama as provinsi_nama,
                pro.pro_kode
            FROM pdpr_wil_kel kel
            INNER JOIN pdpr_wil_kec kec ON kel.kec_id = kec.id
            INNER JOIN pdpr_wil_kab kab ON kec.kab_id = kab.id
            INNER JOIN pdpr_wil_pro pro ON kab.pro_id = pro.id
            WHERE kab.id = ?
            ORDER BY kec.nama, kel.nama
        ", [$this->kabupatenId]);

        // Get all vote data from hr_dpr_ri_kec for this kabupaten
        $voteDataByKecamatan = \DB::select("
            SELECT
                kec.kec_kode,
                hr.chart,
                hr.tbl,
                hr.ts
            FROM pdpr_wil_kec kec
            LEFT JOIN hr_dpr_ri_kec hr ON kec.kec_kode = hr.kec_kode
            WHERE kec.kab_id = ?
            AND hr.tbl IS NOT NULL
            AND hr.tbl != ''
            AND hr.tbl != '\"\"'
            AND hr.tbl != '[]'
        ", [$this->kabupatenId]);

        // Create a map of vote data by kecamatan
        $voteMap = [];
        foreach($voteDataByKecamatan as $voteData) {
            $tbl = json_decode($voteData->tbl, true);
            $chart = json_decode($voteData->chart, true);

            if (is_array($tbl)) {
                foreach($tbl as $kelKode => $desaVotes) {
                    $voteMap[$kelKode] = [
                        'votes' => $desaVotes,
                        'chart' => $chart ?? []
                    ];
                }
            }
        }

        // Transform desa data and add vote data
        return collect($allDesa)->map(function($row) use ($voteMap) {
            $kelKode = $row->kel_kode;

            return [
                'kelurahan_info' => (object)[
                    'id' => $row->kelurahan_id,
                    'nama_kelurahan' => $row->kelurahan_nama,
                    'kel_kode' => $kelKode ?? ''
                ],
                'kecamatan_info' => (object)[
                    'id' => $row->kecamatan_id,
                    'nama_kecamatan' => $row->kecamatan_nama,
                    'kec_kode' => $row->kec_kode ?? ''
                ],
                'kabupaten_info' => (object)[
                    'id' => $row->kabupaten_id,
                    'nama_kabupaten' => $row->kabupaten_nama,
                    'kab_kode' => $row->kab_kode ?? ''
                ],
                'provinsi_info' => (object)[
                    'id' => $row->provinsi_id,
                    'nama_provinsi' => $row->provinsi_nama,
                    'pro_kode' => $row->pro_kode ?? ''
                ],
                'vote_data' => (object)[
                    'votes' => $voteMap[$kelKode]['votes'] ?? [],
                    'chart' => $voteMap[$kelKode]['chart'] ?? [],
                    'has_data' => isset($voteMap[$kelKode])
                ]
            ];
        });
    }
}
