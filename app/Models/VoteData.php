<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VoteData extends Model
{
    protected $table = 'hr_dpr_ri_kel';

    public static function getVoteDataByKabupaten($kabupatenId)
    {
        // Optimized query using subquery for TPS count to avoid expensive LEFT JOIN
        $query = "
            SELECT
                pro.nama as pro_nama,
                pro.pro_kode,
                kab.nama as kab_nama,
                kab.kab_kode,
                k.nama as kec_nama,
                k.kec_kode,
                kel.nama as kel_nama,
                kel.kel_kode,
                kel.id as kel_id,
                hr.chart,
                hr.tbl,
                hr.ts,
                COALESCE(tps_count.jumlah_tps, 0) as jumlah_tps,
                COALESCE(kel.total_dpt, 0) as total_dpt
            FROM hr_dpr_ri_kel hr
            JOIN pdpr_wil_kel kel ON hr.kel_id = kel.id
            JOIN pdpr_wil_kec k ON kel.kec_id = k.id
            JOIN pdpr_wil_kab kab ON k.kab_id = kab.id
            JOIN pdpr_wil_pro pro ON kab.pro_id = pro.id
            LEFT JOIN (
                SELECT kel_id, COUNT(id) as jumlah_tps
                FROM pdpr_wil_tps
                WHERE kab_id = ?
                GROUP BY kel_id
            ) tps_count ON kel.id = tps_count.kel_id
            WHERE kel.kab_id = ?
            ORDER BY k.nama, kel.nama
        ";

        return DB::select($query, [$kabupatenId, $kabupatenId]);
    }

    public static function getPartyData()
    {
        $query = "SELECT nomor_urut, partai_singkat, nama FROM partai ORDER BY nomor_urut";
        return DB::select($query);
    }

    public static function getSuaraPartai($chartJson, $partaiId)
    {
        if (empty($chartJson)) return 0;
        $data = json_decode($chartJson, true);
        return isset($data[$partaiId]['jml_suara_total']) ? $data[$partaiId]['jml_suara_total'] : 0;
    }

    public static function getSuaraPartaiSaja($chartJson, $partaiId)
    {
        if (empty($chartJson)) return 0;
        $data = json_decode($chartJson, true);
        return isset($data[$partaiId]['jml_suara_partai']) ? $data[$partaiId]['jml_suara_partai'] : 0;
    }

    public static function hitungJumlahTPS($tblJson)
    {
        if (empty($tblJson)) return 0;
        $data = json_decode($tblJson, true);
        return $data ? count($data) : 0;
    }

    public static function getSuaraCaleg($tblJson)
    {
        if (empty($tblJson)) return [];
        $data = json_decode($tblJson, true);
        $result = [];

        if ($data && is_array($data)) {
            foreach($data as $tps_code => $tps_data) {
                // Format baru: data caleg langsung di level desa/TPS
                if (is_array($tps_data)) {
                    foreach($tps_data as $caleg_id => $suara) {
                        if ($caleg_id !== 'null' && is_numeric($caleg_id)) {
                            if (!isset($result[$caleg_id])) {
                                $result[$caleg_id] = 0;
                            }
                            $result[$caleg_id] += (int)$suara;
                        }
                    }
                }
                // Format lama: data caleg dalam nested 'caleg'
                elseif (isset($tps_data['caleg']) && is_array($tps_data['caleg'])) {
                    foreach($tps_data['caleg'] as $caleg_id => $suara) {
                        if (!isset($result[$caleg_id])) {
                            $result[$caleg_id] = 0;
                        }
                        $result[$caleg_id] += (int)$suara;
                    }
                }
            }
        }

        return $result;
    }

    public static function getCalegData()
    {
        $query = "
            SELECT
                id,
                nama,
                nomor_urut,
                jenis_kelamin,
                partai_id,
                dapil_id,
                dapil_nama
            FROM dpr_ri_caleg
            ORDER BY partai_id, nomor_urut
        ";
        return DB::select($query);
    }

    public static function getCalegDataByDapil($dapilIds = null)
    {
        $query = "
            SELECT
                id,
                nama,
                nomor_urut,
                jenis_kelamin,
                partai_id,
                dapil_id,
                dapil_nama
            FROM dpr_ri_caleg
            WHERE dapil_id IS NOT NULL
        ";

        $params = [];
        if ($dapilIds && !empty($dapilIds)) {
            $placeholders = implode(',', array_fill(0, count($dapilIds), '?'));
            $query .= " AND dapil_id IN ($placeholders)";
            $params = $dapilIds;
        }

        $query .= " ORDER BY dapil_id, partai_id, nomor_urut";

        return DB::select($query, $params);
    }

    public static function getDapilByKabupatenFromHR($kabupatenId)
    {
        // Get dapil from hr_dpr_ri_kel table (most accurate source)
        $query = "
            SELECT DISTINCT dapil_id, dapil_nama
            FROM hr_dpr_ri_kel
            WHERE kab_id = ?
            LIMIT 1
        ";

        $result = DB::select($query, [$kabupatenId]);
        return $result;
    }

    public static function getDapilByKabupaten($kabupatenId)
    {
        // Get kabupaten name first
        $kabupatenInfo = DB::selectOne("SELECT nama FROM pdpr_wil_kab WHERE id = ?", [$kabupatenId]);
        if (!$kabupatenInfo) {
            return [];
        }

        $kabupatenName = $kabupatenInfo->nama;

        // Try to find exact dapil mapping first using specific kabupaten rules
        $dapilId = null;

        // Specific mappings for multi-dapil provinces/regions
        if (strpos($kabupatenName, 'PALEMBANG') !== false || strpos($kabupatenName, 'OGAN KOMERING ULU') !== false || strpos($kabupatenName, 'MUARA ENIM') !== false) {
            $dapilId = 7637; // SUMATERA SELATAN I
        } elseif (strpos($kabupatenName, 'LUBUK LINGGAU') !== false || strpos($kabupatenName, 'OGAN KOMERING ILIR') !== false || strpos($kabupatenName, 'OGAN ILIR') !== false) {
            $dapilId = 7638; // SUMATERA SELATAN II
        }

        // If we have specific dapil ID, return it
        if ($dapilId) {
            $result = DB::select("SELECT dapil_id, dapil_nama FROM dpr_ri_caleg WHERE dapil_id = ? LIMIT 1", [$dapilId]);
            if (!empty($result)) {
                return $result;
            }
        }

        // Fallback to general regional matching (take first result only)
        $query = "
            SELECT DISTINCT dapil_id, dapil_nama
            FROM dpr_ri_caleg
            WHERE dapil_nama IS NOT NULL
            AND (
                dapil_nama LIKE ? OR
                dapil_nama LIKE ? OR
                dapil_nama LIKE ?
            )
            ORDER BY dapil_id
            LIMIT 1
        ";

        // Extract province/region from kabupaten name for matching
        $regionPatterns = [];
        if (strpos($kabupatenName, 'ACEH') !== false || strpos($kabupatenName, 'BANDA ACEH') !== false) {
            $regionPatterns = ['%ACEH I%', '%ACEH%', '%ACEH%'];
        } elseif (strpos($kabupatenName, 'SUMATERA UTARA') !== false || strpos($kabupatenName, 'MEDAN') !== false) {
            $regionPatterns = ['%SUMATERA UTARA I%', '%SUMATERA UTARA%', '%SUMATERA UTARA%'];
        } elseif (strpos($kabupatenName, 'SUMATERA BARAT') !== false || strpos($kabupatenName, 'PADANG') !== false) {
            $regionPatterns = ['%SUMATERA BARAT I%', '%SUMATERA BARAT%', '%SUMATERA BARAT%'];
        } elseif (strpos($kabupatenName, 'RIAU') !== false || strpos($kabupatenName, 'PEKANBARU') !== false) {
            $regionPatterns = ['%RIAU I%', '%RIAU%', '%RIAU%'];
        } elseif (strpos($kabupatenName, 'JAMBI') !== false) {
            $regionPatterns = ['%JAMBI%', '%JAMBI%', '%JAMBI%'];
        } elseif (strpos($kabupatenName, 'SUMATERA SELATAN') !== false || strpos($kabupatenName, 'PALEMBANG') !== false || strpos($kabupatenName, 'LUBUK LINGGAU') !== false) {
            $regionPatterns = ['%SUMATERA SELATAN I%', '%SUMATERA SELATAN%', '%SUMATERA SELATAN%'];
        } elseif (strpos($kabupatenName, 'BENGKULU') !== false) {
            $regionPatterns = ['%BENGKULU%', '%BENGKULU%', '%BENGKULU%'];
        } elseif (strpos($kabupatenName, 'LAMPUNG') !== false || strpos($kabupatenName, 'BANDAR LAMPUNG') !== false) {
            $regionPatterns = ['%LAMPUNG%', '%LAMPUNG%', '%LAMPUNG%'];
        } elseif (strpos($kabupatenName, 'JAKARTA') !== false || strpos($kabupatenName, 'DKI') !== false) {
            $regionPatterns = ['%JAKARTA I%', '%JAKARTA%', '%DKI%'];
        } elseif (strpos($kabupatenName, 'JAWA BARAT') !== false || strpos($kabupatenName, 'BANDUNG') !== false || strpos($kabupatenName, 'BOGOR') !== false) {
            $regionPatterns = ['%JAWA BARAT I%', '%JAWA BARAT%', '%JAWA BARAT%'];
        } elseif (strpos($kabupatenName, 'JAWA TENGAH') !== false || strpos($kabupatenName, 'SEMARANG') !== false) {
            $regionPatterns = ['%JAWA TENGAH I%', '%JAWA TENGAH%', '%JAWA TENGAH%'];
        } elseif (strpos($kabupatenName, 'YOGYAKARTA') !== false || strpos($kabupatenName, 'YOGYA') !== false) {
            $regionPatterns = ['%YOGYAKARTA%', '%YOGYA%', '%YOGYAKARTA%'];
        } elseif (strpos($kabupatenName, 'JAWA TIMUR') !== false || strpos($kabupatenName, 'SURABAYA') !== false) {
            $regionPatterns = ['%JAWA TIMUR I%', '%JAWA TIMUR%', '%JAWA TIMUR%'];
        } elseif (strpos($kabupatenName, 'BANTEN') !== false) {
            $regionPatterns = ['%BANTEN%', '%BANTEN%', '%BANTEN%'];
        } elseif (strpos($kabupatenName, 'BALI') !== false || strpos($kabupatenName, 'DENPASAR') !== false) {
            $regionPatterns = ['%BALI%', '%BALI%', '%BALI%'];
        } else {
            // Default fallback
            $regionPatterns = ['%' . strtoupper($kabupatenName) . '%', '%%', '%%'];
        }

        return DB::select($query, $regionPatterns);
    }

    public static function getVoteDataByKelurahan($kelurahanId)
    {
        $query = "
            SELECT
                k.nama as kec_nama,
                kel.nama as kel_nama,
                hr.chart,
                hr.tbl,
                hr.ts,
                COUNT(tps.id) as jumlah_tps,
                COALESCE(kel.total_dpt, 0) as total_dpt
            FROM hr_dpr_ri_kel hr
            JOIN pdpr_wil_kel kel ON hr.kel_id = kel.id
            JOIN pdpr_wil_kec k ON kel.kec_id = k.id
            LEFT JOIN pdpr_wil_tps tps ON kel.id = tps.kel_id
            WHERE kel.id = ?
            GROUP BY hr.kel_id, k.nama, kel.nama, hr.chart, hr.tbl, hr.ts, kel.total_dpt
        ";

        return DB::select($query, [$kelurahanId]);
    }

    public static function getKelurahanInfo($kelurahanId)
    {
        $query = "
            SELECT
                kel.nama as kelurahan_name,
                k.nama as kecamatan_name,
                kab.nama as kabupaten_name,
                pro.nama as province_name,
                pro.id as province_id,
                kab.id as kabupaten_id,
                k.id as kecamatan_id
            FROM pdpr_wil_kel kel
            JOIN pdpr_wil_kec k ON kel.kec_id = k.id
            JOIN pdpr_wil_kab kab ON k.kab_id = kab.id
            JOIN pdpr_wil_pro pro ON kab.pro_id = pro.id
            WHERE kel.id = ?
        ";

        $result = DB::select($query, [$kelurahanId]);
        return $result ? (array) $result[0] : null;
    }

    public static function getSuaraCalegPerDesa($tblJson, $calegId)
    {
        if (empty($tblJson)) return [];
        $data = json_decode($tblJson, true);
        $result = [];

        if ($data && is_array($data)) {
            foreach($data as $desa_code => $desa_data) {
                if (is_array($desa_data) && isset($desa_data[$calegId])) {
                    $result[$desa_code] = (int)$desa_data[$calegId];
                }
            }
        }

        return $result;
    }

    public static function getDPTCount($tblJson)
    {
        if (empty($tblJson)) return 0;
        $data = json_decode($tblJson, true);
        $totalDpt = 0;

        if ($data && is_array($data)) {
            foreach ($data as $tps_data) {
                // Try to find DPT information in the data structure
                // This is a placeholder - adjust based on actual data structure
                if (is_array($tps_data) && isset($tps_data['dpt'])) {
                    $totalDpt += (int)$tps_data['dpt'];
                }
            }
        }

        return $totalDpt;
    }

    public static function getSuaraTidakSah($tblJson)
    {
        if (empty($tblJson)) return 0;
        $data = json_decode($tblJson, true);
        $totalTidakSah = 0;

        if ($data && is_array($data)) {
            foreach ($data as $tps_data) {
                // Try to find invalid votes information in the data structure
                // This is a placeholder - adjust based on actual data structure
                if (is_array($tps_data) && isset($tps_data['suara_tidak_sah'])) {
                    $totalTidakSah += (int)$tps_data['suara_tidak_sah'];
                } elseif (is_array($tps_data) && isset($tps_data['tidak_sah'])) {
                    $totalTidakSah += (int)$tps_data['tidak_sah'];
                }
            }
        }

        return $totalTidakSah;
    }

    public static function getTpsDataByKelurahan($kelurahanId)
    {
        $query = "
            SELECT
                tps.id,
                tps.tps_nama,
                tps.no_tps,
                tps.dpt_l,
                tps.dpt_p,
                tps.total_dpt,
                hr.chart,
                hr.tbl
            FROM pdpr_wil_tps tps
            LEFT JOIN hr_dpr_ri_kel hr ON tps.kel_id = hr.kel_id
            WHERE tps.kel_id = ?
            ORDER BY tps.no_tps
        ";

        return DB::select($query, [$kelurahanId]);
    }

    public static function getSuaraPartaiPerTps($tblJson, $tpsCode, $partaiId)
    {
        if (empty($tblJson)) return 0;
        $data = json_decode($tblJson, true);

        if (!$data || !is_array($data)) return 0;

        // Try different TPS code formats
        $tpsCodeVariants = [
            $tpsCode,
            str_pad($tpsCode, 3, '0', STR_PAD_LEFT),
            $tpsCode + 0, // Remove leading zeros
        ];

        $tpsData = null;
        foreach ($tpsCodeVariants as $variant) {
            if (isset($data[$variant])) {
                $tpsData = $data[$variant];
                break;
            }
        }

        if (!$tpsData || !is_array($tpsData)) return 0;

        // Sum votes for specific party from all caleg in that TPS
        $total = 0;
        foreach ($tpsData as $calegId => $votes) {
            if ($calegId !== 'null' && is_numeric($calegId)) {
                // Get caleg info to determine if it belongs to the party
                $caleg = DB::selectOne("SELECT partai_id FROM dpr_ri_caleg WHERE id = ?", [$calegId]);
                if ($caleg && $caleg->partai_id == $partaiId) {
                    $total += (int)$votes;
                }
            }
        }

        return $total;
    }

    public static function getSuaraCalegPerTps($tblJson, $tpsCode, $calegId)
    {
        if (empty($tblJson)) return 0;
        $data = json_decode($tblJson, true);

        if (!$data || !is_array($data)) return 0;

        // Try different TPS code formats
        $tpsCodeVariants = [
            $tpsCode,
            str_pad($tpsCode, 3, '0', STR_PAD_LEFT),
            $tpsCode + 0, // Remove leading zeros
        ];

        foreach ($tpsCodeVariants as $variant) {
            if (isset($data[$variant]) && isset($data[$variant][$calegId])) {
                return (int)$data[$variant][$calegId];
            }
        }

        return 0;
    }

    // Memory-optimized methods for TPS page
    public static function getOptimizedTpsDataByKecamatan($kecamatanId)
    {
        // Get only essential TPS data with limited records to prevent memory issues
        $query = "
            SELECT
                tps.id,
                tps.tps_nama,
                tps.no_tps,
                tps.total_dpt,
                kel.nama as kelurahan_nama,
                kel.id as kelurahan_id
            FROM pdpr_wil_tps tps
            JOIN pdpr_wil_kel kel ON tps.kel_id = kel.id
            WHERE kel.kec_id = ?
            ORDER BY kel.nama, tps.no_tps
            LIMIT 200
        ";

        return DB::select($query, [$kecamatanId]);
    }

    public static function getEssentialPartyData()
    {
        // Get only essential party data (no large fields)
        $query = "SELECT nomor_urut, partai_singkat, nama FROM partai ORDER BY nomor_urut LIMIT 20";
        return DB::select($query);
    }

    public static function getTpsStatsByKecamatan($kecamatanId)
    {
        // Get aggregated statistics instead of detailed data
        $query = "
            SELECT
                COUNT(tps.id) as total_tps,
                SUM(tps.total_dpt) as total_dpt,
                COUNT(DISTINCT kel.id) as total_kelurahan
            FROM pdpr_wil_tps tps
            JOIN pdpr_wil_kel kel ON tps.kel_id = kel.id
            WHERE kel.kec_id = ?
        ";

        $result = DB::select($query, [$kecamatanId]);
        return $result ? $result[0] : (object)['total_tps' => 0, 'total_dpt' => 0, 'total_kelurahan' => 0];
    }

    public static function getTpsDataWithVotesKecamatan($kecamatanId)
    {
        // Get TPS data with vote data from hs_dpr_ri_tps table (faster access)
        $query = "
            SELECT
                tps.id,
                tps.tps_nama,
                tps.no_tps,
                tps.total_dpt,
                pro.nama as provinsi_nama,
                pro.pro_kode,
                kab.nama as kabupaten_nama,
                kab.kab_kode,
                kec.nama as kecamatan_nama,
                kec.kec_kode,
                kel.nama as kelurahan_nama,
                kel.id as kelurahan_id,
                kel.kel_kode,
                hs.chart as party_vote_data,
                hr.tbl as caleg_vote_data
            FROM pdpr_wil_tps tps
            JOIN pdpr_wil_kel kel ON tps.kel_id = kel.id
            JOIN pdpr_wil_kec kec ON kel.kec_id = kec.id
            JOIN pdpr_wil_kab kab ON kec.kab_id = kab.id
            JOIN pdpr_wil_pro pro ON kab.pro_id = pro.id
            LEFT JOIN hs_dpr_ri_tps hs ON hs.tps_id = tps.id
            LEFT JOIN hr_dpr_ri_kel hr ON hr.kel_id = kel.id
            WHERE kel.kec_id = ?
            ORDER BY kel.nama, tps.no_tps
        ";

        return DB::select($query, [$kecamatanId]);
    }
}