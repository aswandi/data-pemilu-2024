-- ============================================================================
-- EXECUTE KABUPATEN/KOTA JUMLAH TPS UPDATE
-- Script untuk mengupdate kolom jumlah_tps di tabel pdpr_wil_kab
-- dengan data penghitungan TPS dari tabel pdpr_wil_tps
-- Agregasi berdasarkan kab_id (COUNT per kabupaten/kota)
-- ============================================================================

-- UPDATE jumlah_tps dari penghitungan tabel pdpr_wil_tps ke pdpr_wil_kab
UPDATE pdpr_wil_kab kab
INNER JOIN (
    SELECT kab_id,
           COUNT(*) as tps_count
    FROM pdpr_wil_tps
    WHERE kab_id IS NOT NULL
    GROUP BY kab_id
) tps_summary ON kab.id = tps_summary.kab_id
SET
    kab.jumlah_tps = tps_summary.tps_count,
    kab.updated_at = CURRENT_TIMESTAMP;

-- VERIFIKASI HASIL UPDATE
SELECT 'KABUPATEN UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'KABUPATEN AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_kabupaten,
    COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) as filled_jumlah_tps,
    COUNT(CASE WHEN jumlah_tps IS NULL THEN 1 END) as empty_jumlah_tps,
    ROUND((COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage,
    MIN(jumlah_tps) as min_tps,
    MAX(jumlah_tps) as max_tps,
    ROUND(AVG(jumlah_tps), 2) as avg_tps_per_kabupaten
FROM pdpr_wil_kab;

-- Top 10 kabupaten/kota dengan TPS terbanyak
SELECT 'TOP 10 KABUPATEN/KOTA HIGHEST TPS COUNT' as title;
SELECT
    kab.nama as nama_kabupaten,
    pro.nama as nama_provinsi,
    kab.jumlah_tps as jumlah_tps,
    CASE
        WHEN kab.nama LIKE '%KOTA%' OR kab.nama LIKE '%JAKARTA%' THEN 'KOTA'
        ELSE 'KABUPATEN'
    END as type
FROM pdpr_wil_kab kab
LEFT JOIN pdpr_wil_pro pro ON kab.pro_id = pro.id
WHERE kab.jumlah_tps IS NOT NULL
ORDER BY kab.jumlah_tps DESC
LIMIT 10;