-- ============================================================================
-- EXECUTE KELURAHAN JUMLAH TPS UPDATE
-- Script untuk mengupdate kolom jumlah_tps di tabel pdpr_wil_kel
-- dengan data penghitungan TPS dari tabel pdpr_wil_tps
-- Agregasi berdasarkan kel_id (COUNT per kelurahan)
-- ============================================================================

-- UPDATE jumlah_tps dari penghitungan tabel pdpr_wil_tps ke pdpr_wil_kel
UPDATE pdpr_wil_kel kel
INNER JOIN (
    SELECT kel_id,
           COUNT(*) as tps_count
    FROM pdpr_wil_tps
    WHERE kel_id IS NOT NULL
    GROUP BY kel_id
) tps_summary ON kel.id = tps_summary.kel_id
SET
    kel.jumlah_tps = tps_summary.tps_count,
    kel.updated_at = CURRENT_TIMESTAMP;

-- VERIFIKASI HASIL UPDATE
SELECT 'UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_kelurahan,
    COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) as filled_jumlah_tps,
    COUNT(CASE WHEN jumlah_tps IS NULL THEN 1 END) as empty_jumlah_tps,
    ROUND((COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage,
    MIN(jumlah_tps) as min_tps,
    MAX(jumlah_tps) as max_tps,
    ROUND(AVG(jumlah_tps), 2) as avg_tps_per_kelurahan
FROM pdpr_wil_kel;

-- Distribusi jumlah TPS per kelurahan setelah update
SELECT 'TPS COUNT DISTRIBUTION PER KELURAHAN' as title;
SELECT
    CASE
        WHEN jumlah_tps IS NULL THEN 'NULL'
        WHEN jumlah_tps = 1 THEN '1 TPS'
        WHEN jumlah_tps BETWEEN 2 AND 5 THEN '2-5 TPS'
        WHEN jumlah_tps BETWEEN 6 AND 10 THEN '6-10 TPS'
        WHEN jumlah_tps BETWEEN 11 AND 15 THEN '11-15 TPS'
        WHEN jumlah_tps BETWEEN 16 AND 20 THEN '16-20 TPS'
        WHEN jumlah_tps > 20 THEN '20+ TPS'
    END as tps_range,
    COUNT(*) as kelurahan_count,
    ROUND(AVG(jumlah_tps), 2) as avg_tps_in_range,
    MIN(jumlah_tps) as min_tps_in_range,
    MAX(jumlah_tps) as max_tps_in_range
FROM pdpr_wil_kel
GROUP BY tps_range
ORDER BY kelurahan_count DESC;

-- Total TPS nasional dari penghitungan kelurahan
SELECT 'NATIONAL TPS SUMMARY FROM KELURAHAN LEVEL' as title;
SELECT
    FORMAT(SUM(jumlah_tps), 0) as total_tps_from_kelurahan,
    FORMAT(AVG(jumlah_tps), 2) as rata_rata_tps_per_kelurahan,
    COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) as kelurahan_with_tps
FROM pdpr_wil_kel;

-- Sample hasil update per kecamatan
SELECT 'SAMPLE BY KECAMATAN' as title;
SELECT
    kec.nama as nama_kecamatan,
    kab.nama as nama_kabupaten,
    COUNT(kel.id) as jumlah_kelurahan,
    FORMAT(SUM(kel.jumlah_tps), 0) as total_tps_kecamatan,
    FORMAT(AVG(kel.jumlah_tps), 2) as rata_rata_tps_per_kelurahan
FROM pdpr_wil_kel kel
INNER JOIN pdpr_wil_kec kec ON kel.kec_id = kec.id
INNER JOIN pdpr_wil_kab kab ON kel.kab_id = kab.id
WHERE kel.jumlah_tps IS NOT NULL
GROUP BY kec.id, kec.nama, kab.nama
ORDER BY SUM(kel.jumlah_tps) DESC
LIMIT 10;

-- Top 10 kelurahan dengan TPS terbanyak
SELECT 'TOP 10 KELURAHAN HIGHEST TPS COUNT' as title;
SELECT
    kel.nama as nama_kelurahan,
    kec.nama as nama_kecamatan,
    kab.nama as nama_kabupaten,
    pro.nama as nama_provinsi,
    kel.jumlah_tps as jumlah_tps
FROM pdpr_wil_kel kel
LEFT JOIN pdpr_wil_kec kec ON kel.kec_id = kec.id
LEFT JOIN pdpr_wil_kab kab ON kel.kab_id = kab.id
LEFT JOIN pdpr_wil_pro pro ON kel.pro_id = pro.id
WHERE kel.jumlah_tps IS NOT NULL
ORDER BY kel.jumlah_tps DESC
LIMIT 10;

-- Verifikasi konsistensi dengan data TPS
SELECT 'CONSISTENCY CHECK WITH TPS DATA' as title;
SELECT
    COUNT(*) as kelurahan_updated,
    COUNT(CASE WHEN kel.jumlah_tps = tps_count.calculated_count THEN 1 END) as consistent_counts,
    COUNT(CASE WHEN kel.jumlah_tps != tps_count.calculated_count THEN 1 END) as inconsistent_counts
FROM pdpr_wil_kel kel
INNER JOIN (
    SELECT kel_id, COUNT(*) as calculated_count
    FROM pdpr_wil_tps
    WHERE kel_id IS NOT NULL
    GROUP BY kel_id
) tps_count ON kel.id = tps_count.kel_id
WHERE kel.jumlah_tps IS NOT NULL;

-- Kelurahan yang tidak ter-update
SELECT 'NOT UPDATED KELURAHAN' as title;
SELECT
    COUNT(*) as not_updated_count,
    'Kelurahan without TPS data' as reason
FROM pdpr_wil_kel
WHERE jumlah_tps IS NULL;

-- Statistik per provinsi
SELECT 'TPS COUNT BY PROVINSI' as title;
SELECT
    pro.nama as nama_provinsi,
    COUNT(kel.id) as jumlah_kelurahan,
    FORMAT(SUM(kel.jumlah_tps), 0) as total_tps_provinsi,
    FORMAT(AVG(kel.jumlah_tps), 2) as rata_rata_tps_per_kelurahan
FROM pdpr_wil_kel kel
INNER JOIN pdpr_wil_pro pro ON kel.pro_id = pro.id
WHERE kel.jumlah_tps IS NOT NULL
GROUP BY pro.id, pro.nama
ORDER BY SUM(kel.jumlah_tps) DESC
LIMIT 10;