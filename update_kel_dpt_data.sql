-- ============================================================================
-- Script Update Data DPT Kelurahan
-- Mengupdate kolom dpt_l, dpt_p, dan total_dpt di tabel pdpr_wil_kel
-- Data dihitung dari penjumlahan data TPS di tabel pdpr_wil_tps
-- Agregasi berdasarkan kel_id
-- ============================================================================

-- Cek status data sebelum update
SELECT 'BEFORE UPDATE - DATA STATUS' as title;
SELECT
    'pdpr_wil_kel' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt
FROM pdpr_wil_kel

UNION ALL

SELECT
    'pdpr_wil_tps_aggregated' as table_name,
    COUNT(DISTINCT kel_id) as total_records,
    COUNT(DISTINCT kel_id) as filled_dpt_l,
    COUNT(DISTINCT kel_id) as filled_dpt_p,
    COUNT(DISTINCT kel_id) as filled_total_dpt
FROM pdpr_wil_tps
WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL;

-- Analisis potensi matching
SELECT 'MATCHING ANALYSIS' as title;
SELECT
    COUNT(DISTINCT k.id) as total_kel_records,
    COUNT(DISTINCT tps_summary.kel_id) as kel_with_tps_data,
    ROUND((COUNT(DISTINCT tps_summary.kel_id) / COUNT(DISTINCT k.id)) * 100, 2) as coverage_percentage
FROM pdpr_wil_kel k
LEFT JOIN (
    SELECT kel_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_tps
    WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL
    GROUP BY kel_id
) tps_summary ON k.id = tps_summary.kel_id;

-- Preview data yang akan diupdate (sample 10 records)
SELECT 'PREVIEW UPDATE DATA' as title;
SELECT
    k.id as kel_id,
    k.nama as nama_kelurahan,
    k.dpt_l as current_dpt_l,
    k.dpt_p as current_dpt_p,
    k.total_dpt as current_total_dpt,
    tps_summary.sum_dpt_l as new_dpt_l,
    tps_summary.sum_dpt_p as new_dpt_p,
    tps_summary.sum_total_dpt as new_total_dpt,
    tps_summary.tps_count
FROM pdpr_wil_kel k
INNER JOIN (
    SELECT kel_id,
           COUNT(*) as tps_count,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_tps
    WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL
    GROUP BY kel_id
) tps_summary ON k.id = tps_summary.kel_id
LIMIT 10;