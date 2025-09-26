-- ============================================================================
-- EXECUTE KELURAHAN DPT DATA UPDATE
-- Script untuk mengupdate kolom dpt_l, dpt_p, dan total_dpt
-- di tabel pdpr_wil_kel dari agregasi data tabel pdpr_wil_tps
-- Agregasi berdasarkan kel_id (SUM data per kelurahan)
-- ============================================================================

-- UPDATE data DPT dari agregasi tabel pdpr_wil_tps ke pdpr_wil_kel
UPDATE pdpr_wil_kel k
INNER JOIN (
    SELECT kel_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt,
           COUNT(*) as tps_count
    FROM pdpr_wil_tps
    WHERE dpt_l IS NOT NULL
      AND dpt_p IS NOT NULL
      AND total_dpt IS NOT NULL
    GROUP BY kel_id
) tps_summary ON k.id = tps_summary.kel_id
SET
    k.dpt_l = tps_summary.sum_dpt_l,
    k.dpt_p = tps_summary.sum_dpt_p,
    k.total_dpt = tps_summary.sum_total_dpt,
    k.updated_at = CURRENT_TIMESTAMP
WHERE k.dpt_l IS NULL
  AND k.dpt_p IS NULL
  AND k.total_dpt IS NULL;

-- VERIFIKASI HASIL UPDATE
SELECT 'UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_kelurahan,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt,
    ROUND((COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage
FROM pdpr_wil_kel;

-- Statistik agregat DPT berdasarkan range per kelurahan
SELECT 'DPT DISTRIBUTION PER KELURAHAN' as title;
SELECT
    CASE
        WHEN total_dpt IS NULL THEN 'NULL'
        WHEN total_dpt BETWEEN 1 AND 500 THEN '1-500'
        WHEN total_dpt BETWEEN 501 AND 1000 THEN '501-1000'
        WHEN total_dpt BETWEEN 1001 AND 2000 THEN '1001-2000'
        WHEN total_dpt BETWEEN 2001 AND 3000 THEN '2001-3000'
        WHEN total_dpt BETWEEN 3001 AND 5000 THEN '3001-5000'
        WHEN total_dpt > 5000 THEN '5000+'
    END as dpt_range,
    COUNT(*) as kelurahan_count,
    ROUND(AVG(total_dpt), 2) as avg_dpt,
    MIN(total_dpt) as min_dpt,
    MAX(total_dpt) as max_dpt
FROM pdpr_wil_kel
GROUP BY dpt_range
ORDER BY dpt_range;

-- Total DPT nasional dari level kelurahan
SELECT 'NATIONAL DPT SUMMARY FROM KELURAHAN LEVEL' as title;
SELECT
    FORMAT(SUM(dpt_l), 0) as total_dpt_laki_laki,
    FORMAT(SUM(dpt_p), 0) as total_dpt_perempuan,
    FORMAT(SUM(total_dpt), 0) as total_dpt_nasional,
    FORMAT(AVG(total_dpt), 2) as rata_rata_dpt_per_kelurahan
FROM pdpr_wil_kel
WHERE total_dpt IS NOT NULL;

-- Sample hasil update per provinsi
SELECT 'SAMPLE BY PROVINCE' as title;
SELECT
    p.nama as nama_provinsi,
    COUNT(k.id) as jumlah_kelurahan,
    FORMAT(SUM(k.total_dpt), 0) as total_dpt_provinsi,
    FORMAT(AVG(k.total_dpt), 2) as rata_rata_dpt_per_kel
FROM pdpr_wil_kel k
INNER JOIN pdpr_wil_pro p ON k.pro_id = p.id
WHERE k.total_dpt IS NOT NULL
GROUP BY p.id, p.nama
ORDER BY SUM(k.total_dpt) DESC
LIMIT 10;

-- Top 10 kelurahan dengan DPT tertinggi
SELECT 'TOP 10 KELURAHAN HIGHEST DPT' as title;
SELECT
    k.nama as nama_kelurahan,
    kec.nama as nama_kecamatan,
    kab.nama as nama_kabupaten,
    pro.nama as nama_provinsi,
    FORMAT(k.dpt_l, 0) as dpt_l,
    FORMAT(k.dpt_p, 0) as dpt_p,
    FORMAT(k.total_dpt, 0) as total_dpt
FROM pdpr_wil_kel k
LEFT JOIN pdpr_wil_kec kec ON k.kec_id = kec.id
LEFT JOIN pdpr_wil_kab kab ON k.kab_id = kab.id
LEFT JOIN pdpr_wil_pro pro ON k.pro_id = pro.id
WHERE k.total_dpt IS NOT NULL
ORDER BY k.total_dpt DESC
LIMIT 10;

-- Verifikasi konsistensi dengan data TPS
SELECT 'CONSISTENCY CHECK WITH TPS DATA' as title;
SELECT
    COUNT(*) as kelurahan_updated,
    COUNT(CASE WHEN k.total_dpt = tps_sum.calculated_total THEN 1 END) as consistent_totals,
    COUNT(CASE WHEN k.total_dpt != tps_sum.calculated_total THEN 1 END) as inconsistent_totals
FROM pdpr_wil_kel k
INNER JOIN (
    SELECT kel_id, SUM(total_dpt) as calculated_total
    FROM pdpr_wil_tps
    WHERE total_dpt IS NOT NULL
    GROUP BY kel_id
) tps_sum ON k.id = tps_sum.kel_id
WHERE k.total_dpt IS NOT NULL;

-- Kelurahan yang tidak ter-update
SELECT 'NOT UPDATED KELURAHAN' as title;
SELECT
    COUNT(*) as not_updated_count,
    'Kelurahan without TPS data' as reason
FROM pdpr_wil_kel
WHERE dpt_l IS NULL
  AND dpt_p IS NULL
  AND total_dpt IS NULL;