-- ============================================================================
-- Script Update Kolom no_tps dari tps_nama
-- Mengkonversi: TPS 001 → 1, TPS 010 → 10, KSK 001 → 1, POS 001 → 1
-- Menghapus prefix (TPS/KSK/POS) dan leading zeros
-- ============================================================================

-- Preview hasil sebelum update
SELECT 'PREVIEW CONVERSION' as title;
SELECT
    tps_nama,
    no_tps as current_no_tps,
    CASE
        WHEN tps_nama LIKE 'TPS %' THEN CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED)
        WHEN tps_nama LIKE 'TPS%' AND tps_nama NOT LIKE 'TPS %' THEN CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED)
        WHEN tps_nama LIKE 'KSK %' THEN CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED)
        WHEN tps_nama LIKE 'KSK%' AND tps_nama NOT LIKE 'KSK %' THEN CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED)
        WHEN tps_nama LIKE 'POS %' THEN CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED)
        WHEN tps_nama LIKE 'POS%' AND tps_nama NOT LIKE 'POS %' THEN CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED)
        ELSE NULL
    END AS new_no_tps
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
AND (tps_nama LIKE 'TPS%' OR tps_nama LIKE 'KSK%' OR tps_nama LIKE 'POS%')
LIMIT 10;

-- Statistik sebelum update
SELECT 'BEFORE UPDATE STATS' as title;
SELECT
    SUBSTRING(tps_nama, 1, 3) as prefix,
    COUNT(*) as total_records,
    COUNT(no_tps) as already_filled
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
GROUP BY SUBSTRING(tps_nama, 1, 3)
ORDER BY total_records DESC;