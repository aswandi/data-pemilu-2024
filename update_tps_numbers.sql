-- Script untuk mengupdate kolom no_tps berdasarkan tps_nama
-- Menghapus kata "TPS" dan leading zeros dari nomor

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
        ELSE NULL
    END AS new_no_tps
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
AND (tps_nama LIKE 'TPS %' OR tps_nama LIKE 'TPS%')
LIMIT 20;

-- Backup data sebelum update (opsional)
-- CREATE TABLE pdpr_wil_tps_backup AS SELECT * FROM pdpr_wil_tps;

-- Update kolom no_tps
-- Untuk format "TPS 001" (dengan spasi)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'TPS %'
AND tps_nama IS NOT NULL
AND TRIM(SUBSTRING(tps_nama, 5)) REGEXP '^[0-9]+$';

-- Untuk format "TPS001" (tanpa spasi)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'TPS%'
AND tps_nama NOT LIKE 'TPS %'
AND tps_nama IS NOT NULL
AND TRIM(SUBSTRING(tps_nama, 4)) REGEXP '^[0-9]+$';

-- Verifikasi hasil update
SELECT
    COUNT(*) as total_updated,
    MIN(no_tps) as min_tps,
    MAX(no_tps) as max_tps
FROM pdpr_wil_tps
WHERE no_tps IS NOT NULL;

-- Cek sample hasil setelah update
SELECT
    tps_nama,
    no_tps
FROM pdpr_wil_tps
WHERE no_tps IS NOT NULL
ORDER BY no_tps
LIMIT 20;

-- Cek jika ada data yang tidak terupdate
SELECT
    COUNT(*) as not_updated,
    GROUP_CONCAT(DISTINCT tps_nama LIMIT 10) as sample_names
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
AND no_tps IS NULL;