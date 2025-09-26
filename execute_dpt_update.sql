-- ============================================================================
-- EXECUTE DPT DATA UPDATE
-- Script untuk mengupdate kolom dpt_l, dpt_p, dan total_dpt
-- di tabel pdpr_wil_tps dari tabel tps
-- Matching berdasarkan kel_kode dan no_tps
-- ============================================================================

-- UPDATE data DPT dari tabel tps ke pdpr_wil_tps
UPDATE pdpr_wil_tps p
INNER JOIN tps t ON p.kel_kode = t.kel_kode AND p.no_tps = t.no_tps
SET
    p.dpt_l = t.dpt_l,
    p.dpt_p = t.dpt_p,
    p.total_dpt = (t.dpt_l + t.dpt_p),
    p.updated_at = CURRENT_TIMESTAMP
WHERE t.dpt_l IS NOT NULL
  AND t.dpt_p IS NOT NULL
  AND p.dpt_l IS NULL
  AND p.dpt_p IS NULL;

-- VERIFIKASI HASIL UPDATE
SELECT 'UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_records,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt,
    ROUND((COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage
FROM pdpr_wil_tps;

-- Statistik DPT berdasarkan range
SELECT 'DPT DISTRIBUTION' as title;
SELECT
    CASE
        WHEN total_dpt IS NULL THEN 'NULL'
        WHEN total_dpt BETWEEN 1 AND 100 THEN '1-100'
        WHEN total_dpt BETWEEN 101 AND 200 THEN '101-200'
        WHEN total_dpt BETWEEN 201 AND 300 THEN '201-300'
        WHEN total_dpt BETWEEN 301 AND 400 THEN '301-400'
        WHEN total_dpt > 400 THEN '400+'
    END as dpt_range,
    COUNT(*) as count,
    ROUND(AVG(total_dpt), 2) as avg_dpt
FROM pdpr_wil_tps
GROUP BY dpt_range
ORDER BY dpt_range;

-- Sample hasil update
SELECT 'SAMPLE UPDATED RECORDS' as title;
SELECT
    kel_kode,
    no_tps,
    dpt_l,
    dpt_p,
    total_dpt,
    tps_nama
FROM pdpr_wil_tps
WHERE dpt_l IS NOT NULL
  AND dpt_p IS NOT NULL
ORDER BY RAND()
LIMIT 15;

-- Verifikasi konsistensi total_dpt
SELECT 'CONSISTENCY CHECK' as title;
SELECT
    COUNT(*) as total_with_dpt,
    COUNT(CASE WHEN total_dpt = (dpt_l + dpt_p) THEN 1 END) as consistent_totals,
    COUNT(CASE WHEN total_dpt != (dpt_l + dpt_p) THEN 1 END) as inconsistent_totals
FROM pdpr_wil_tps
WHERE dpt_l IS NOT NULL AND dpt_p IS NOT NULL;

-- Records yang tidak ter-update (jika ada)
SELECT 'NOT UPDATED ANALYSIS' as title;
SELECT
    COUNT(*) as not_updated_count,
    'Records without matching data in tps table' as reason
FROM pdpr_wil_tps
WHERE dpt_l IS NULL
  AND dpt_p IS NULL
  AND total_dpt IS NULL;

-- Top 10 TPS dengan DPT tertinggi
SELECT 'TOP 10 HIGHEST DPT' as title;
SELECT
    kel_kode,
    no_tps,
    tps_nama,
    dpt_l,
    dpt_p,
    total_dpt
FROM pdpr_wil_tps
WHERE total_dpt IS NOT NULL
ORDER BY total_dpt DESC
LIMIT 10;