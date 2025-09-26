-- Script untuk mengupdate kolom no_tps berdasarkan tps_nama
-- Menghapus kata "TPS"/"KSK" dan leading zeros dari nomor
-- Menangani format: TPS 001, TPS001, KSK 001, KSK001

-- Cek data sample sebelum update
SELECT
    tps_nama,
    no_tps,
    -- Preview hasil yang akan diupdate
    CASE
        WHEN tps_nama LIKE 'TPS %' THEN
            CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED)
        WHEN tps_nama LIKE 'TPS%' THEN
            CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED)
        WHEN tps_nama LIKE 'KSK %' THEN
            CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED)
        WHEN tps_nama LIKE 'KSK%' THEN
            CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED)
        ELSE NULL
    END AS new_no_tps
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
AND (tps_nama LIKE 'TPS %' OR tps_nama LIKE 'TPS%' OR tps_nama LIKE 'KSK %' OR tps_nama LIKE 'KSK%')
LIMIT 20;

-- Backup kolom sebelum update (opsional - uncomment jika diperlukan)
-- ALTER TABLE pdpr_wil_tps ADD COLUMN no_tps_backup INT AFTER no_tps;
-- UPDATE pdpr_wil_tps SET no_tps_backup = no_tps WHERE no_tps IS NOT NULL;

-- ==== UPDATE QUERIES ====

-- 1. Update untuk format "TPS 001" (dengan spasi)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'TPS %'
AND tps_nama IS NOT NULL
AND TRIM(SUBSTRING(tps_nama, 5)) REGEXP '^[0-9]+$';

-- 2. Update untuk format "TPS001" (tanpa spasi)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'TPS%'
AND tps_nama NOT LIKE 'TPS %'
AND tps_nama IS NOT NULL
AND TRIM(SUBSTRING(tps_nama, 4)) REGEXP '^[0-9]+$';

-- 3. Update untuk format "KSK 001" (dengan spasi)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'KSK %'
AND tps_nama IS NOT NULL
AND TRIM(SUBSTRING(tps_nama, 5)) REGEXP '^[0-9]+$';

-- 4. Update untuk format "KSK001" (tanpa spasi)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'KSK%'
AND tps_nama NOT LIKE 'KSK %'
AND tps_nama IS NOT NULL
AND TRIM(SUBSTRING(tps_nama, 4)) REGEXP '^[0-9]+$';

-- ==== VERIFIKASI HASIL ====

-- Hitung total yang berhasil diupdate
SELECT
    'Total Updated' as status,
    COUNT(*) as count
FROM pdpr_wil_tps
WHERE no_tps IS NOT NULL;

-- Statistik update berdasarkan prefix
SELECT
    CASE
        WHEN tps_nama LIKE 'TPS%' THEN 'TPS'
        WHEN tps_nama LIKE 'KSK%' THEN 'KSK'
        ELSE 'Other'
    END as prefix_type,
    COUNT(*) as total_records,
    COUNT(no_tps) as updated_records,
    MIN(no_tps) as min_no,
    MAX(no_tps) as max_no
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
GROUP BY prefix_type;

-- Sample hasil update
SELECT
    tps_nama,
    no_tps
FROM pdpr_wil_tps
WHERE no_tps IS NOT NULL
ORDER BY RAND()
LIMIT 20;

-- Cek data yang mungkin gagal diupdate
SELECT
    COUNT(*) as failed_updates,
    GROUP_CONCAT(DISTINCT tps_nama LIMIT 10) as sample_failed
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
AND (tps_nama LIKE 'TPS%' OR tps_nama LIKE 'KSK%')
AND no_tps IS NULL;

-- Verifikasi konversi beberapa contoh spesifik
SELECT 'Verification Examples' as title;
SELECT tps_nama, no_tps FROM pdpr_wil_tps WHERE tps_nama IN ('TPS 001', 'TPS 010', 'TPS 100', 'KSK 001', 'KSK 010') LIMIT 10;