-- ============================================================================
-- EXECUTE PROVINSI COMPLETE STATISTICS UPDATE
-- Script untuk mengupdate kolom jumlah_kecamatan dan jumlah_kelurahan
-- di tabel pdpr_wil_pro dengan data dari tabel terkait
-- ============================================================================

-- UPDATE jumlah_kecamatan dari penghitungan tabel pdpr_wil_kec
UPDATE pdpr_wil_pro pro
INNER JOIN (
    SELECT pro_id,
           COUNT(DISTINCT id) as kecamatan_count
    FROM pdpr_wil_kec
    WHERE pro_id IS NOT NULL
    GROUP BY pro_id
) kec_summary ON pro.id = kec_summary.pro_id
SET
    pro.jumlah_kecamatan = kec_summary.kecamatan_count,
    pro.updated_at = CURRENT_TIMESTAMP;

-- UPDATE jumlah_kelurahan dari penghitungan tabel pdpr_wil_kel
UPDATE pdpr_wil_pro pro
INNER JOIN (
    SELECT pro_id,
           COUNT(DISTINCT id) as kelurahan_count
    FROM pdpr_wil_kel
    WHERE pro_id IS NOT NULL
    GROUP BY pro_id
) kel_summary ON pro.id = kel_summary.pro_id
SET
    pro.jumlah_kelurahan = kel_summary.kelurahan_count,
    pro.updated_at = CURRENT_TIMESTAMP;

-- VERIFIKASI HASIL UPDATE
SELECT 'PROVINSI COMPLETE STATS UPDATE COMPLETED' as status;

-- Statistik setelah update
SELECT 'PROVINSI COMPLETE STATISTICS' as title;
SELECT
    COUNT(*) as total_provinsi,
    COUNT(CASE WHEN jumlah_kecamatan IS NOT NULL THEN 1 END) as filled_kecamatan,
    COUNT(CASE WHEN jumlah_kelurahan IS NOT NULL THEN 1 END) as filled_kelurahan,
    COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) as filled_tps,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_dpt
FROM pdpr_wil_pro;

-- Sample data untuk verifikasi
SELECT 'SAMPLE PROVINSI DATA WITH COMPLETE STATS' as title;
SELECT
    nama as nama_provinsi,
    jumlah_kecamatan,
    jumlah_kelurahan,
    jumlah_tps,
    FORMAT(total_dpt, 0) as total_dpt
FROM pdpr_wil_pro
WHERE jumlah_kecamatan IS NOT NULL
ORDER BY total_dpt DESC
LIMIT 10;

-- National summary
SELECT 'NATIONAL SUMMARY FROM PROVINSI LEVEL' as title;
SELECT
    FORMAT(SUM(jumlah_kecamatan), 0) as total_kecamatan_nasional,
    FORMAT(SUM(jumlah_kelurahan), 0) as total_kelurahan_nasional,
    FORMAT(SUM(jumlah_tps), 0) as total_tps_nasional,
    FORMAT(SUM(total_dpt), 0) as total_dpt_nasional
FROM pdpr_wil_pro
WHERE jumlah_kecamatan IS NOT NULL;