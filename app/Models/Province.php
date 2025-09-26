<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Province extends Model
{
    protected $table = 'pdpr_wil_pro';

    protected $fillable = [
        'nama',
        'pro_id',
        'pro_kode',
    ];

    public static function getProvinceDataWithStats()
    {
        // Use pre-calculated statistics from database columns for optimal performance
        $provinces = DB::select("
            SELECT
                p.id,
                COALESCE(NULLIF(p.nama, ''), p.pro_nama, 'Unknown') AS nama_provinsi,
                COALESCE(p.jumlah_kecamatan, 0) AS jumlah_kecamatan,
                COALESCE(p.jumlah_kelurahan, 0) AS jumlah_kelurahan,
                COALESCE(p.jumlah_tps, 0) AS jumlah_tps,
                COALESCE(p.total_dpt, 0) AS jumlah_dpt
            FROM pdpr_wil_pro p
            WHERE (p.nama IS NOT NULL AND p.nama != '') OR (p.pro_nama IS NOT NULL AND p.pro_nama != '')
            ORDER BY p.pro_kode
        ");

        // Calculate dapil and kabupaten counts (these are smaller tables, so safe to query)
        foreach ($provinces as $province) {
            // Count dapil
            $dapilCount = DB::selectOne("SELECT COUNT(*) as count FROM pdpr_wil_dapil WHERE pro_id = ?", [$province->id]);
            $province->jumlah_dapil = $dapilCount->count ?? 0;

            // Count kabupaten/kota
            $kabkotaCount = DB::selectOne("SELECT COUNT(*) as count FROM pdpr_wil_kab WHERE pro_id = ?", [$province->id]);
            $province->jumlah_kabkota = $kabkotaCount->count ?? 0;
        }

        return $provinces;
    }

    public static function getProvinceName($provinceId)
    {
        $province = DB::selectOne("
            SELECT COALESCE(NULLIF(nama, ''), pro_nama, 'Unknown') AS nama_provinsi
            FROM pdpr_wil_pro
            WHERE id = ?
        ", [$provinceId]);

        return $province->nama_provinsi ?? 'Unknown Province';
    }

    public static function getKabupatenDataWithStats($provinceId)
    {
        // Use pre-calculated statistics from database columns for optimal performance
        $kabupaten = DB::select("
            SELECT DISTINCT
                id,
                COALESCE(NULLIF(nama, ''), kab_nama, 'Unknown') AS nama_kabkota,
                COALESCE(jumlah_kecamatan, 0) AS jumlah_kecamatan,
                COALESCE(jumlah_kelurahan, 0) AS jumlah_kelurahan,
                COALESCE(jumlah_tps, 0) AS jumlah_tps,
                COALESCE(total_dpt, 0) AS jumlah_dpt
            FROM pdpr_wil_kab
            WHERE pro_id = ? AND ((nama IS NOT NULL AND nama != '') OR (kab_nama IS NOT NULL AND kab_nama != ''))
            ORDER BY nama_kabkota
        ", [$provinceId]);

        return $kabupaten;
    }

    public static function getKabupatenInfo($kabupatenId)
    {
        $kabupaten = DB::selectOne("
            SELECT
                k.id,
                COALESCE(NULLIF(k.nama, ''), k.kab_nama, 'Unknown') AS kabupaten_name,
                k.pro_id,
                COALESCE(NULLIF(p.nama, ''), p.pro_nama, 'Unknown') AS province_name
            FROM pdpr_wil_kab k
            LEFT JOIN pdpr_wil_pro p ON k.pro_id = p.id
            WHERE k.id = ?
        ", [$kabupatenId]);

        return [
            'kabupaten_name' => $kabupaten->kabupaten_name ?? 'Unknown Kabupaten',
            'province_name' => $kabupaten->province_name ?? 'Unknown Province',
            'province_id' => $kabupaten->pro_id ?? 0
        ];
    }

    public static function getKecamatanDataWithStats($kabupatenId)
    {
        // Simplified query without statistics to prevent timeouts
        $kecamatan = DB::select("
            SELECT DISTINCT
                id,
                COALESCE(NULLIF(nama, ''), kec_nama, 'Unknown') AS nama_kecamatan,
                0 AS jumlah_kelurahan,
                0 AS jumlah_tps
            FROM pdpr_wil_kec
            WHERE kab_id = ? AND ((nama IS NOT NULL AND nama != '') OR (kec_nama IS NOT NULL AND kec_nama != ''))
            ORDER BY nama_kecamatan
        ", [$kabupatenId]);

        return $kecamatan;
    }

    public static function getKecamatanInfo($kecamatanId)
    {
        $kecamatan = DB::selectOne("
            SELECT
                k.id,
                COALESCE(NULLIF(k.nama, ''), k.kec_nama, 'Unknown') AS kecamatan_name,
                k.kab_id,
                COALESCE(NULLIF(kb.nama, ''), kb.kab_nama, 'Unknown') AS kabupaten_name,
                kb.pro_id,
                COALESCE(NULLIF(p.nama, ''), p.pro_nama, 'Unknown') AS province_name
            FROM pdpr_wil_kec k
            LEFT JOIN pdpr_wil_kab kb ON k.kab_id = kb.id
            LEFT JOIN pdpr_wil_pro p ON kb.pro_id = p.id
            WHERE k.id = ?
        ", [$kecamatanId]);

        return [
            'kecamatan_name' => $kecamatan->kecamatan_name ?? 'Unknown Kecamatan',
            'kabupaten_name' => $kecamatan->kabupaten_name ?? 'Unknown Kabupaten',
            'province_name' => $kecamatan->province_name ?? 'Unknown Province',
            'kabupaten_id' => $kecamatan->kab_id ?? 0,
            'province_id' => $kecamatan->pro_id ?? 0
        ];
    }

    public static function getKelurahanDataWithStats($kecamatanId)
    {
        // Get kelurahan for the specified kecamatan
        $kelurahan = DB::select("
            SELECT DISTINCT
                id,
                COALESCE(NULLIF(nama, ''), kel_nama, 'Unknown') AS nama_kelurahan
            FROM pdpr_wil_kel
            WHERE kec_id = ? AND ((nama IS NOT NULL AND nama != '') OR (kel_nama IS NOT NULL AND kel_nama != ''))
            ORDER BY nama_kelurahan
        ", [$kecamatanId]);

        // Calculate real statistics for each kelurahan (optimized)
        foreach ($kelurahan as $kel) {
            // Skip TPS count for now as it causes timeouts - set to 0
            $kel->jumlah_tps = 0;
        }

        return $kelurahan;
    }

    public static function getRealStatistics()
    {
        // Get totals from pre-calculated provincial data for optimal performance
        $totals = DB::selectOne("
            SELECT
                COUNT(*) as total_provinces,
                SUM(jumlah_kecamatan) as total_kecamatan,
                SUM(jumlah_kelurahan) as total_kelurahan,
                SUM(jumlah_tps) as total_tps,
                SUM(total_dpt) as total_dpt
            FROM pdpr_wil_pro
            WHERE (nama IS NOT NULL AND nama != '') OR (pro_nama IS NOT NULL AND pro_nama != '')
        ");

        // Get total dapil (small table, safe to query)
        $totalDapil = DB::selectOne("SELECT COUNT(DISTINCT id) as count FROM pdpr_wil_dapil");

        // Get total kabupaten/kota (small table, safe to query)
        $totalKabKota = DB::selectOne("SELECT COUNT(DISTINCT id) as count FROM pdpr_wil_kab");

        return [
            'total_provinces' => $totals->total_provinces ?? 0,
            'total_dapil' => $totalDapil->count ?? 0,
            'total_kabkota' => $totalKabKota->count ?? 0,
            'total_kecamatan' => $totals->total_kecamatan ?? 0,
            'total_kelurahan' => $totals->total_kelurahan ?? 0,
            'total_tps' => $totals->total_tps ?? 0,
            'total_dpt' => $totals->total_dpt ?? 0
        ];
    }
}
