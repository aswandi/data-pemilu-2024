<?php

namespace App\Exports;

use App\Models\VoteData;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KabupatenCalegMultiDesaExport implements WithMultipleSheets
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

        // OPTIMIZED: Get all data in single query with JOIN
        $allKelurahanData = $this->getOptimizedKelurahanData();

        // Split into chunks of 50 villages per sheet
        $kelurahanChunks = $allKelurahanData->chunk(50);

        foreach($kelurahanChunks as $index => $kelurahanChunk) {
            $sheetNumber = $index + 1;
            $isLastSheet = ($index === $kelurahanChunks->count() - 1);
            $sheetTitle = "Sheet {$sheetNumber}";

            if ($kelurahanChunks->count() == 1) {
                // If only one sheet, use kabupaten name and it's the last sheet
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

            $sheets[] = new KabupatenCalegPerDesaSheetExport(
                $this->kabupatenId,
                $this->kabupatenName,
                $this->provinceName,
                $kelurahanChunk,
                $sheetTitle,
                $isLastSheet
            );
        }

        return $sheets;
    }

    /**
     * Optimized method to get all kelurahan data with single query
     */
    private function getOptimizedKelurahanData()
    {
        // Single optimized query with JOINs instead of N+1 queries
        $data = \DB::select("
            SELECT
                kel.id as kelurahan_id,
                kel.nama as kelurahan_nama,
                kel.kel_kode,
                kec.id as kecamatan_id,
                kec.nama as kecamatan_nama,
                kec.kec_kode,
                kab.nama as kabupaten_nama,
                kab.kab_kode,
                pro.nama as provinsi_nama,
                pro.pro_kode,
                hs.chart,
                hs.tbl,
                hs.ts
            FROM pdpr_wil_kel kel
            INNER JOIN pdpr_wil_kec kec ON kel.kec_id = kec.id
            INNER JOIN pdpr_wil_kab kab ON kec.kab_id = kab.id
            INNER JOIN pdpr_wil_pro pro ON kab.pro_id = pro.id
            LEFT JOIN hs_dpr_ri_kel hs ON kel.id = hs.kel_id
            WHERE kel.kab_id = ?
            AND hs.id IS NOT NULL
            ORDER BY kec.nama, kel.nama
        ", [$this->kabupatenId]);

        // Transform to collection with proper structure
        return collect($data)->map(function($row) {
            return [
                'kelurahan_info' => (object)[
                    'id' => $row->kelurahan_id,
                    'nama_kelurahan' => $row->kelurahan_nama,
                    'kel_kode' => $row->kel_kode ?? ''
                ],
                'kecamatan_info' => (object)[
                    'id' => $row->kecamatan_id,
                    'nama_kecamatan' => $row->kecamatan_nama,
                    'kec_kode' => $row->kec_kode ?? ''
                ],
                'kabupaten_info' => (object)[
                    'nama_kabupaten' => $row->kabupaten_nama ?? '',
                    'kab_kode' => $row->kab_kode ?? ''
                ],
                'provinsi_info' => (object)[
                    'nama_provinsi' => $row->provinsi_nama ?? '',
                    'pro_kode' => $row->pro_kode ?? ''
                ],
                'vote_data' => (object)[
                    'chart' => $row->chart,
                    'tbl' => $row->tbl,
                    'ts' => $row->ts
                ]
            ];
        });
    }
}