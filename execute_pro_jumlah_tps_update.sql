-- ============================================================================
-- EXECUTE PROVINSI JUMLAH TPS UPDATE
-- Script untuk mengupdate kolom jumlah_tps di tabel pdpr_wil_pro
-- dengan data penghitungan TPS dari tabel pdpr_wil_tps
-- Agregasi berdasarkan pro_id (COUNT per provinsi)
-- ============================================================================

-- UPDATE jumlah_tps dari penghitungan tabel pdpr_wil_tps ke pdpr_wil_pro
UPDATE pdpr_wil_pro pro
INNER JOIN (
    SELECT pro_id,
           COUNT(*) as tps_count
    FROM pdpr_wil_tps
    WHERE pro_id IS NOT NULL
    GROUP BY pro_id
) tps_summary ON pro.id = tps_summary.pro_id
SET
    pro.jumlah_tps = tps_summary.tps_count,
    pro.updated_at = CURRENT_TIMESTAMP;

-- VERIFIKASI HASIL UPDATE
SELECT 'PROVINSI UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'PROVINSI AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_provinsi,
    COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) as filled_jumlah_tps,
    COUNT(CASE WHEN jumlah_tps IS NULL THEN 1 END) as empty_jumlah_tps,
    ROUND((COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage,
    MIN(jumlah_tps) as min_tps,
    MAX(jumlah_tps) as max_tps,
    ROUND(AVG(jumlah_tps), 2) as avg_tps_per_provinsi
FROM pdpr_wil_pro;

-- Top 10 provinsi dengan TPS terbanyak
SELECT 'TOP 10 PROVINSI HIGHEST TPS COUNT' as title;
SELECT
    pro.nama as nama_provinsi,
    pro.jumlah_tps as jumlah_tps,
    (SELECT COUNT(*) FROM pdpr_wil_kab WHERE pro_id = pro.id AND jumlah_tps IS NOT NULL) as jumlah_kab_kota_active
FROM pdpr_wil_pro pro
WHERE pro.jumlah_tps IS NOT NULL
ORDER BY pro.jumlah_tps DESC
LIMIT 10;

-- Verifikasi total TPS nasional
SELECT 'NATIONAL TPS SUMMARY VERIFICATION' as title;
SELECT
    FORMAT(SUM(jumlah_tps), 0) as total_tps_from_provinsi,
    (SELECT FORMAT(COUNT(*), 0) FROM pdpr_wil_tps) as total_tps_actual,
    CASE
        WHEN SUM(jumlah_tps) = (SELECT COUNT(*) FROM pdpr_wil_tps) THEN 'MATCH ✅'
        ELSE 'MISMATCH ❌'
    END as verification_status
FROM pdpr_wil_pro
WHERE jumlah_tps IS NOT NULL;