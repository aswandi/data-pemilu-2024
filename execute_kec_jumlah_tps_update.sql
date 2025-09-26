-- ============================================================================
-- EXECUTE KECAMATAN JUMLAH TPS UPDATE
-- Script untuk mengupdate kolom jumlah_tps di tabel pdpr_wil_kec
-- dengan data penghitungan TPS dari tabel pdpr_wil_tps
-- Agregasi berdasarkan kec_id (COUNT per kecamatan)
-- ============================================================================

-- UPDATE jumlah_tps dari penghitungan tabel pdpr_wil_tps ke pdpr_wil_kec
UPDATE pdpr_wil_kec kec
INNER JOIN (
    SELECT kec_id,
           COUNT(*) as tps_count
    FROM pdpr_wil_tps
    WHERE kec_id IS NOT NULL
    GROUP BY kec_id
) tps_summary ON kec.id = tps_summary.kec_id
SET
    kec.jumlah_tps = tps_summary.tps_count,
    kec.updated_at = CURRENT_TIMESTAMP;

-- VERIFIKASI HASIL UPDATE
SELECT 'KECAMATAN UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'KECAMATAN AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_kecamatan,
    COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) as filled_jumlah_tps,
    COUNT(CASE WHEN jumlah_tps IS NULL THEN 1 END) as empty_jumlah_tps,
    ROUND((COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage,
    MIN(jumlah_tps) as min_tps,
    MAX(jumlah_tps) as max_tps,
    ROUND(AVG(jumlah_tps), 2) as avg_tps_per_kecamatan
FROM pdpr_wil_kec;

-- Top 10 kecamatan dengan TPS terbanyak
SELECT 'TOP 10 KECAMATAN HIGHEST TPS COUNT' as title;
SELECT
    kec.nama as nama_kecamatan,
    kab.nama as nama_kabupaten,
    pro.nama as nama_provinsi,
    kec.jumlah_tps as jumlah_tps
FROM pdpr_wil_kec kec
LEFT JOIN pdpr_wil_kab kab ON kec.kab_id = kab.id
LEFT JOIN pdpr_wil_pro pro ON kec.pro_id = pro.id
WHERE kec.jumlah_tps IS NOT NULL
ORDER BY kec.jumlah_tps DESC
LIMIT 10;