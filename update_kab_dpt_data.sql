-- ============================================================================
-- Script Update Data DPT Kabupaten/Kota
-- Mengupdate kolom dpt_l, dpt_p, dan total_dpt di tabel pdpr_wil_kab
-- Data dihitung dari penjumlahan data Kecamatan di tabel pdpr_wil_kec
-- Agregasi berdasarkan kab_id
-- ============================================================================

-- Cek status data sebelum update
SELECT 'BEFORE UPDATE - DATA STATUS' as title;
SELECT
    'pdpr_wil_kab' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt
FROM pdpr_wil_kab

UNION ALL

SELECT
    'pdpr_wil_kec_aggregated' as table_name,
    COUNT(DISTINCT kab_id) as total_records,
    COUNT(DISTINCT kab_id) as filled_dpt_l,
    COUNT(DISTINCT kab_id) as filled_dpt_p,
    COUNT(DISTINCT kab_id) as filled_total_dpt
FROM pdpr_wil_kec
WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL;

-- Analisis potensi matching
SELECT 'MATCHING ANALYSIS' as title;
SELECT
    COUNT(DISTINCT kab.id) as total_kab_records,
    COUNT(DISTINCT kec_summary.kab_id) as kab_with_kec_data,
    ROUND((COUNT(DISTINCT kec_summary.kab_id) / COUNT(DISTINCT kab.id)) * 100, 2) as coverage_percentage
FROM pdpr_wil_kab kab
LEFT JOIN (
    SELECT kab_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_kec
    WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL
    GROUP BY kab_id
) kec_summary ON kab.id = kec_summary.kab_id;

-- Preview data yang akan diupdate (sample 10 records)
SELECT 'PREVIEW UPDATE DATA' as title;
SELECT
    kab.id as kab_id,
    kab.nama as nama_kabupaten,
    kab.dpt_l as current_dpt_l,
    kab.dpt_p as current_dpt_p,
    kab.total_dpt as current_total_dpt,
    kec_summary.sum_dpt_l as new_dpt_l,
    kec_summary.sum_dpt_p as new_dpt_p,
    kec_summary.sum_total_dpt as new_total_dpt,
    kec_summary.kec_count
FROM pdpr_wil_kab kab
INNER JOIN (
    SELECT kab_id,
           COUNT(*) as kec_count,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_kec
    WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL
    GROUP BY kab_id
) kec_summary ON kab.id = kec_summary.kab_id
LIMIT 10;