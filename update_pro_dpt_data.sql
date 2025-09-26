-- ============================================================================
-- Script Update Data DPT Provinsi
-- Mengupdate kolom dpt_l, dpt_p, dan total_dpt di tabel pdpr_wil_pro
-- Data dihitung dari penjumlahan data Kabupaten/Kota di tabel pdpr_wil_kab
-- Agregasi berdasarkan pro_id
-- ============================================================================

-- Cek status data sebelum update
SELECT 'BEFORE UPDATE - DATA STATUS' as title;
SELECT
    'pdpr_wil_pro' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt
FROM pdpr_wil_pro

UNION ALL

SELECT
    'pdpr_wil_kab_aggregated' as table_name,
    COUNT(DISTINCT pro_id) as total_records,
    COUNT(DISTINCT pro_id) as filled_dpt_l,
    COUNT(DISTINCT pro_id) as filled_dpt_p,
    COUNT(DISTINCT pro_id) as filled_total_dpt
FROM pdpr_wil_kab
WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL;

-- Analisis potensi matching
SELECT 'MATCHING ANALYSIS' as title;
SELECT
    COUNT(DISTINCT pro.id) as total_pro_records,
    COUNT(DISTINCT kab_summary.pro_id) as pro_with_kab_data,
    ROUND((COUNT(DISTINCT kab_summary.pro_id) / COUNT(DISTINCT pro.id)) * 100, 2) as coverage_percentage
FROM pdpr_wil_pro pro
LEFT JOIN (
    SELECT pro_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_kab
    WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL
    GROUP BY pro_id
) kab_summary ON pro.id = kab_summary.pro_id;

-- Preview data yang akan diupdate (sample 10 records)
SELECT 'PREVIEW UPDATE DATA' as title;
SELECT
    pro.id as pro_id,
    pro.nama as nama_provinsi,
    pro.dpt_l as current_dpt_l,
    pro.dpt_p as current_dpt_p,
    pro.total_dpt as current_total_dpt,
    kab_summary.sum_dpt_l as new_dpt_l,
    kab_summary.sum_dpt_p as new_dpt_p,
    kab_summary.sum_total_dpt as new_total_dpt,
    kab_summary.kab_count
FROM pdpr_wil_pro pro
INNER JOIN (
    SELECT pro_id,
           COUNT(*) as kab_count,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_kab
    WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL
    GROUP BY pro_id
) kab_summary ON pro.id = kab_summary.pro_id
LIMIT 10;