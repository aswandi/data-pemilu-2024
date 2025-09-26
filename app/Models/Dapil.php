<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dapil extends Model
{
    protected $table = 'pdpr_wil_dapil';

    protected $fillable = [
        'nama',
        'pro_id',
        'dapil_id',
        'dapil_kode',
    ];

    public static function getDapilDataByProvince($provinceId)
    {
        // Get dapil data for the specified province with kabupaten count
        $dapils = DB::select("
            SELECT DISTINCT
                d.id,
                d.nama as nama_dapil,
                d.dapil_kode,
                p.nama as nama_provinsi,
                (SELECT COUNT(DISTINCT kab_id)
                 FROM pdpr_wil_dapil d2
                 WHERE d2.nama = d.nama
                   AND d2.pro_id = d.pro_id
                   AND d2.kab_id IS NOT NULL) as jumlah_kabkota
            FROM pdpr_wil_dapil d
            LEFT JOIN pdpr_wil_pro p ON d.pro_id = p.id
            WHERE d.pro_id = ? AND d.nama IS NOT NULL AND d.nama != ''
            ORDER BY d.nama
        ", [$provinceId]);

        return $dapils;
    }

    public static function getProvinceDataWithKabupatenCount($provinceId)
    {
        // Get province info with kabupaten count
        $provinceData = DB::selectOne("
            SELECT
                p.id,
                COALESCE(NULLIF(p.nama, ''), p.pro_nama, 'Unknown') AS nama_provinsi,
                COUNT(DISTINCT k.id) as jumlah_kabkota
            FROM pdpr_wil_pro p
            LEFT JOIN pdpr_wil_kab k ON p.id = k.pro_id
            WHERE p.id = ?
            GROUP BY p.id, p.nama, p.pro_nama
        ", [$provinceId]);

        return $provinceData;
    }

    public static function getProvinceInfo($provinceId)
    {
        $province = DB::selectOne("
            SELECT COALESCE(NULLIF(nama, ''), pro_nama, 'Unknown') AS nama_provinsi
            FROM pdpr_wil_pro
            WHERE id = ?
        ", [$provinceId]);

        return [
            'nama_provinsi' => $province->nama_provinsi ?? 'Unknown Province'
        ];
    }
}