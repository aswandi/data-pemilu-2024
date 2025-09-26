-- ============================================================================
-- EXECUTE TPS NUMBER UPDATE
-- Script untuk mengupdate kolom no_tps dari tps_nama
-- Format: TPS 001 → 1, TPS 010 → 10, KSK 001 → 1, POS 001 → 1
-- ============================================================================

-- 1. UPDATE TPS dengan spasi (TPS 001, TPS 010, dll)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'TPS %'
  AND tps_nama IS NOT NULL
  AND TRIM(SUBSTRING(tps_nama, 5)) REGEXP '^[0-9]+$'
  AND no_tps IS NULL;

-- 2. UPDATE TPS tanpa spasi (TPS001, TPS010, dll)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'TPS%'
  AND tps_nama NOT LIKE 'TPS %'
  AND tps_nama IS NOT NULL
  AND TRIM(SUBSTRING(tps_nama, 4)) REGEXP '^[0-9]+$'
  AND no_tps IS NULL;

-- 3. UPDATE KSK dengan spasi (KSK 001, KSK 010, dll)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'KSK %'
  AND tps_nama IS NOT NULL
  AND TRIM(SUBSTRING(tps_nama, 5)) REGEXP '^[0-9]+$'
  AND no_tps IS NULL;

-- 4. UPDATE KSK tanpa spasi (KSK001, KSK010, dll)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'KSK%'
  AND tps_nama NOT LIKE 'KSK %'
  AND tps_nama IS NOT NULL
  AND TRIM(SUBSTRING(tps_nama, 4)) REGEXP '^[0-9]+$'
  AND no_tps IS NULL;

-- 5. UPDATE POS dengan spasi (POS 001, POS 010, dll)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'POS %'
  AND tps_nama IS NOT NULL
  AND TRIM(SUBSTRING(tps_nama, 5)) REGEXP '^[0-9]+$'
  AND no_tps IS NULL;

-- 6. UPDATE POS tanpa spasi (POS001, POS010, dll)
UPDATE pdpr_wil_tps
SET no_tps = CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED),
    updated_at = CURRENT_TIMESTAMP
WHERE tps_nama LIKE 'POS%'
  AND tps_nama NOT LIKE 'POS %'
  AND tps_nama IS NOT NULL
  AND TRIM(SUBSTRING(tps_nama, 4)) REGEXP '^[0-9]+$'
  AND no_tps IS NULL;

-- VERIFIKASI HASIL UPDATE
SELECT 'UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT
    SUBSTRING(tps_nama, 1, 3) as prefix,
    COUNT(*) as total_records,
    COUNT(no_tps) as updated_records,
    ROUND((COUNT(no_tps) / COUNT(*)) * 100, 2) as success_percentage,
    MIN(no_tps) as min_no,
    MAX(no_tps) as max_no
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
  AND (tps_nama LIKE 'TPS%' OR tps_nama LIKE 'KSK%' OR tps_nama LIKE 'POS%')
GROUP BY SUBSTRING(tps_nama, 1, 3)
ORDER BY total_records DESC;

-- Sample hasil update
SELECT 'SAMPLE RESULTS' as title;
SELECT tps_nama, no_tps
FROM pdpr_wil_tps
WHERE no_tps IS NOT NULL
  AND (tps_nama LIKE 'TPS%' OR tps_nama LIKE 'KSK%' OR tps_nama LIKE 'POS%')
ORDER BY RAND()
LIMIT 15;

-- Data yang gagal diupdate (jika ada)
SELECT 'FAILED UPDATES (if any)' as title;
SELECT COUNT(*) as failed_count
FROM pdpr_wil_tps
WHERE tps_nama IS NOT NULL
  AND (tps_nama LIKE 'TPS%' OR tps_nama LIKE 'KSK%' OR tps_nama LIKE 'POS%')
  AND no_tps IS NULL;