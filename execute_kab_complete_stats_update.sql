-- ============================================================================
-- EXECUTE KABUPATEN COMPLETE STATISTICS UPDATE
-- Script untuk mengupdate kolom jumlah_kecamatan dan jumlah_kelurahan
-- di tabel pdpr_wil_kab dengan data dari tabel terkait
-- ============================================================================

-- UPDATE jumlah_kecamatan dari penghitungan tabel pdpr_wil_kec
UPDATE pdpr_wil_kab kab
INNER JOIN (
    SELECT kab_id,
           COUNT(DISTINCT id) as kecamatan_count
    FROM pdpr_wil_kec
    WHERE kab_id IS NOT NULL
    GROUP BY kab_id
) kec_summary ON kab.id = kec_summary.kab_id
SET
    kab.jumlah_kecamatan = kec_summary.kecamatan_count,
    kab.updated_at = CURRENT_TIMESTAMP;

-- UPDATE jumlah_kelurahan dari penghitungan tabel pdpr_wil_kel
UPDATE pdpr_wil_kab kab
INNER JOIN (
    SELECT kab_id,
           COUNT(DISTINCT id) as kelurahan_count
    FROM pdpr_wil_kel
    WHERE kab_id IS NOT NULL
    GROUP BY kab_id
) kel_summary ON kab.id = kel_summary.kab_id
SET
    kab.jumlah_kelurahan = kel_summary.kelurahan_count,
    kab.updated_at = CURRENT_TIMESTAMP;

-- VERIFIKASI HASIL UPDATE
SELECT 'KABUPATEN COMPLETE STATS UPDATE COMPLETED' as status;

-- Statistik setelah update
SELECT 'KABUPATEN COMPLETE STATISTICS' as title;
SELECT
    COUNT(*) as total_kabupaten,
    COUNT(CASE WHEN jumlah_kecamatan IS NOT NULL THEN 1 END) as filled_kecamatan,
    COUNT(CASE WHEN jumlah_kelurahan IS NOT NULL THEN 1 END) as filled_kelurahan,
    COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) as filled_tps,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_dpt
FROM pdpr_wil_kab;

-- Sample data untuk verifikasi (provinsi Sumatera Barat - ID 191087)
SELECT 'SAMPLE KABUPATEN DATA - SUMATERA BARAT' as title;
SELECT
    nama as nama_kabupaten,
    jumlah_kecamatan,
    jumlah_kelurahan,
    jumlah_tps,
    FORMAT(total_dpt, 0) as total_dpt
FROM pdpr_wil_kab
WHERE pro_id = 191087 AND jumlah_kecamatan IS NOT NULL
ORDER BY total_dpt DESC
LIMIT 10;