-- ============================================================================
-- Script Update Data DPT Kecamatan
-- Mengupdate kolom dpt_l, dpt_p, dan total_dpt di tabel pdpr_wil_kec
-- Data dihitung dari penjumlahan data Kelurahan di tabel pdpr_wil_kel
-- Agregasi berdasarkan kec_id
-- ============================================================================

-- Cek status data sebelum update
SELECT 'BEFORE UPDATE - DATA STATUS' as title;
SELECT
    'pdpr_wil_kec' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt
FROM pdpr_wil_kec

UNION ALL

SELECT
    'pdpr_wil_kel_aggregated' as table_name,
    COUNT(DISTINCT kec_id) as total_records,
    COUNT(DISTINCT kec_id) as filled_dpt_l,
    COUNT(DISTINCT kec_id) as filled_dpt_p,
    COUNT(DISTINCT kec_id) as filled_total_dpt
FROM pdpr_wil_kel
WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL;

-- Analisis potensi matching
SELECT 'MATCHING ANALYSIS' as title;
SELECT
    COUNT(DISTINCT kec.id) as total_kec_records,
    COUNT(DISTINCT kel_summary.kec_id) as kec_with_kel_data,
    ROUND((COUNT(DISTINCT kel_summary.kec_id) / COUNT(DISTINCT kec.id)) * 100, 2) as coverage_percentage
FROM pdpr_wil_kec kec
LEFT JOIN (
    SELECT kec_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_kel
    WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL
    GROUP BY kec_id
) kel_summary ON kec.id = kel_summary.kec_id;

-- Preview data yang akan diupdate (sample 10 records)
SELECT 'PREVIEW UPDATE DATA' as title;
SELECT
    kec.id as kec_id,
    kec.nama as nama_kecamatan,
    kec.dpt_l as current_dpt_l,
    kec.dpt_p as current_dpt_p,
    kec.total_dpt as current_total_dpt,
    kel_summary.sum_dpt_l as new_dpt_l,
    kel_summary.sum_dpt_p as new_dpt_p,
    kel_summary.sum_total_dpt as new_total_dpt,
    kel_summary.kel_count
FROM pdpr_wil_kec kec
INNER JOIN (
    SELECT kec_id,
           COUNT(*) as kel_count,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_kel
    WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL
    GROUP BY kec_id
) kel_summary ON kec.id = kel_summary.kec_id
LIMIT 10;