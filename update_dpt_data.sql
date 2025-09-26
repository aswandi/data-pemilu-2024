-- ============================================================================
-- Script Update Data DPT (Daftar Pemilih Tetap)
-- Mengupdate kolom dpt_l, dpt_p, dan total_dpt di tabel pdpr_wil_tps
-- Data diambil dari tabel tps berdasarkan matching kel_kode dan no_tps
-- ============================================================================

-- Cek status data sebelum update
SELECT 'BEFORE UPDATE - DATA STATUS' as title;
SELECT
    'pdpr_wil_tps' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt
FROM pdpr_wil_tps

UNION ALL

SELECT
    'tps' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(dpt_l + dpt_p) as calculated_total
FROM tps;

-- Cek jumlah records yang bisa di-match
SELECT 'MATCHING ANALYSIS' as title;
SELECT COUNT(*) as total_matches
FROM pdpr_wil_tps p
INNER JOIN tps t ON p.kel_kode = t.kel_kode AND p.no_tps = t.no_tps;

-- Preview data yang akan diupdate (sample 10 records)
SELECT 'PREVIEW UPDATE DATA' as title;
SELECT
    p.id as pdpr_id,
    p.kel_kode,
    p.no_tps,
    p.dpt_l as current_dpt_l,
    p.dpt_p as current_dpt_p,
    p.total_dpt as current_total_dpt,
    t.dpt_l as new_dpt_l,
    t.dpt_p as new_dpt_p,
    (t.dpt_l + t.dpt_p) as new_total_dpt
FROM pdpr_wil_tps p
INNER JOIN tps t ON p.kel_kode = t.kel_kode AND p.no_tps = t.no_tps
WHERE t.dpt_l IS NOT NULL AND t.dpt_p IS NOT NULL
LIMIT 10;