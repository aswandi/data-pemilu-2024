-- ============================================================================
-- Script Update Jumlah TPS di Tabel pdpr_wil_kel
-- Mengupdate kolom jumlah_tps di tabel pdpr_wil_kel
-- Data dihitung dari penghitungan TPS di tabel pdpr_wil_tps per kelurahan
-- Agregasi berdasarkan kel_id
-- ============================================================================

-- Cek status data sebelum update
SELECT 'BEFORE UPDATE - DATA STATUS' as title;
SELECT
    'pdpr_wil_kel' as table_name,
    COUNT(*) as total_records,
    COUNT(CASE WHEN jumlah_tps IS NOT NULL THEN 1 END) as filled_jumlah_tps,
    COUNT(CASE WHEN jumlah_tps IS NULL THEN 1 END) as empty_jumlah_tps
FROM pdpr_wil_kel

UNION ALL

SELECT
    'pdpr_wil_tps_per_kelurahan' as table_name,
    COUNT(DISTINCT kel_id) as total_records,
    COUNT(DISTINCT kel_id) as filled_jumlah_tps,
    0 as empty_jumlah_tps
FROM pdpr_wil_tps
WHERE kel_id IS NOT NULL;

-- Analisis potensi matching
SELECT 'MATCHING ANALYSIS' as title;
SELECT
    COUNT(DISTINCT kel.id) as total_kel_records,
    COUNT(DISTINCT tps_summary.kel_id) as kel_with_tps_data,
    ROUND((COUNT(DISTINCT tps_summary.kel_id) / COUNT(DISTINCT kel.id)) * 100, 2) as coverage_percentage
FROM pdpr_wil_kel kel
LEFT JOIN (
    SELECT kel_id, COUNT(*) as tps_count
    FROM pdpr_wil_tps
    WHERE kel_id IS NOT NULL
    GROUP BY kel_id
) tps_summary ON kel.id = tps_summary.kel_id;

-- Preview data yang akan diupdate (sample 10 records)
SELECT 'PREVIEW UPDATE DATA' as title;
SELECT
    kel.id as kel_id,
    kel.nama as nama_kelurahan,
    kel.jumlah_tps as current_jumlah_tps,
    tps_summary.tps_count as new_jumlah_tps,
    kec.nama as nama_kecamatan,
    kab.nama as nama_kabupaten
FROM pdpr_wil_kel kel
INNER JOIN (
    SELECT kel_id, COUNT(*) as tps_count
    FROM pdpr_wil_tps
    WHERE kel_id IS NOT NULL
    GROUP BY kel_id
) tps_summary ON kel.id = tps_summary.kel_id
LEFT JOIN pdpr_wil_kec kec ON kel.kec_id = kec.id
LEFT JOIN pdpr_wil_kab kab ON kel.kab_id = kab.id
LIMIT 10;

-- Distribusi jumlah TPS per kelurahan (sebelum update)
SELECT 'TPS COUNT DISTRIBUTION ANALYSIS' as title;
SELECT
    CASE
        WHEN tps_count = 1 THEN '1 TPS'
        WHEN tps_count BETWEEN 2 AND 5 THEN '2-5 TPS'
        WHEN tps_count BETWEEN 6 AND 10 THEN '6-10 TPS'
        WHEN tps_count BETWEEN 11 AND 15 THEN '11-15 TPS'
        WHEN tps_count BETWEEN 16 AND 20 THEN '16-20 TPS'
        WHEN tps_count > 20 THEN '20+ TPS'
    END as tps_range,
    COUNT(*) as kelurahan_count,
    ROUND(AVG(tps_count), 2) as avg_tps_per_kelurahan
FROM (
    SELECT kel_id, COUNT(*) as tps_count
    FROM pdpr_wil_tps
    WHERE kel_id IS NOT NULL
    GROUP BY kel_id
) tps_summary
GROUP BY tps_range
ORDER BY kelurahan_count DESC;