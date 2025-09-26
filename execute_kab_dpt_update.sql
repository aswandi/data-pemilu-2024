-- ============================================================================
-- EXECUTE KABUPATEN/KOTA DPT DATA UPDATE
-- Script untuk mengupdate kolom dpt_l, dpt_p, dan total_dpt
-- di tabel pdpr_wil_kab dari agregasi data tabel pdpr_wil_kec
-- Agregasi berdasarkan kab_id (SUM data per kabupaten/kota)
-- ============================================================================

-- UPDATE data DPT dari agregasi tabel pdpr_wil_kec ke pdpr_wil_kab
UPDATE pdpr_wil_kab kab
INNER JOIN (
    SELECT kab_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt,
           COUNT(*) as kec_count
    FROM pdpr_wil_kec
    WHERE dpt_l IS NOT NULL
      AND dpt_p IS NOT NULL
      AND total_dpt IS NOT NULL
    GROUP BY kab_id
) kec_summary ON kab.id = kec_summary.kab_id
SET
    kab.dpt_l = kec_summary.sum_dpt_l,
    kab.dpt_p = kec_summary.sum_dpt_p,
    kab.total_dpt = kec_summary.sum_total_dpt,
    kab.updated_at = CURRENT_TIMESTAMP
WHERE kab.dpt_l IS NULL
  AND kab.dpt_p IS NULL
  AND kab.total_dpt IS NULL;

-- VERIFIKASI HASIL UPDATE
SELECT 'UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_kabupaten,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt,
    ROUND((COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage
FROM pdpr_wil_kab;

-- Statistik agregat DPT berdasarkan range per kabupaten/kota
SELECT 'DPT DISTRIBUTION PER KABUPATEN/KOTA' as title;
SELECT
    CASE
        WHEN total_dpt IS NULL THEN 'NULL'
        WHEN total_dpt BETWEEN 1 AND 100000 THEN '1-100K'
        WHEN total_dpt BETWEEN 100001 AND 300000 THEN '100K-300K'
        WHEN total_dpt BETWEEN 300001 AND 500000 THEN '300K-500K'
        WHEN total_dpt BETWEEN 500001 AND 1000000 THEN '500K-1M'
        WHEN total_dpt BETWEEN 1000001 AND 2000000 THEN '1M-2M'
        WHEN total_dpt > 2000000 THEN '2M+'
    END as dpt_range,
    COUNT(*) as kabupaten_count,
    ROUND(AVG(total_dpt), 2) as avg_dpt,
    MIN(total_dpt) as min_dpt,
    MAX(total_dpt) as max_dpt
FROM pdpr_wil_kab
GROUP BY dpt_range
ORDER BY dpt_range;

-- Total DPT nasional dari level kabupaten/kota
SELECT 'NATIONAL DPT SUMMARY FROM KABUPATEN LEVEL' as title;
SELECT
    FORMAT(SUM(dpt_l), 0) as total_dpt_laki_laki,
    FORMAT(SUM(dpt_p), 0) as total_dpt_perempuan,
    FORMAT(SUM(total_dpt), 0) as total_dpt_nasional,
    FORMAT(AVG(total_dpt), 2) as rata_rata_dpt_per_kabupaten
FROM pdpr_wil_kab
WHERE total_dpt IS NOT NULL;

-- Sample hasil update per provinsi
SELECT 'SAMPLE BY PROVINCE' as title;
SELECT
    pro.nama as nama_provinsi,
    COUNT(kab.id) as jumlah_kabupaten,
    FORMAT(SUM(kab.total_dpt), 0) as total_dpt_provinsi,
    FORMAT(AVG(kab.total_dpt), 2) as rata_rata_dpt_per_kab
FROM pdpr_wil_kab kab
INNER JOIN pdpr_wil_pro pro ON kab.pro_id = pro.id
WHERE kab.total_dpt IS NOT NULL
GROUP BY pro.id, pro.nama
ORDER BY SUM(kab.total_dpt) DESC
LIMIT 10;

-- Top 10 kabupaten/kota dengan DPT tertinggi
SELECT 'TOP 10 KABUPATEN/KOTA HIGHEST DPT' as title;
SELECT
    kab.nama as nama_kabupaten,
    pro.nama as nama_provinsi,
    FORMAT(kab.dpt_l, 0) as dpt_l,
    FORMAT(kab.dpt_p, 0) as dpt_p,
    FORMAT(kab.total_dpt, 0) as total_dpt,
    CASE
        WHEN kab.nama LIKE '%KOTA%' OR kab.nama LIKE '%JAKARTA%' THEN 'KOTA'
        ELSE 'KABUPATEN'
    END as type
FROM pdpr_wil_kab kab
LEFT JOIN pdpr_wil_pro pro ON kab.pro_id = pro.id
WHERE kab.total_dpt IS NOT NULL
ORDER BY kab.total_dpt DESC
LIMIT 10;

-- Verifikasi konsistensi dengan data kecamatan
SELECT 'CONSISTENCY CHECK WITH KECAMATAN DATA' as title;
SELECT
    COUNT(*) as kabupaten_updated,
    COUNT(CASE WHEN kab.total_dpt = kec_sum.calculated_total THEN 1 END) as consistent_totals,
    COUNT(CASE WHEN kab.total_dpt != kec_sum.calculated_total THEN 1 END) as inconsistent_totals
FROM pdpr_wil_kab kab
INNER JOIN (
    SELECT kab_id, SUM(total_dpt) as calculated_total
    FROM pdpr_wil_kec
    WHERE total_dpt IS NOT NULL
    GROUP BY kab_id
) kec_sum ON kab.id = kec_sum.kab_id
WHERE kab.total_dpt IS NOT NULL;

-- Kabupaten/kota yang tidak ter-update
SELECT 'NOT UPDATED KABUPATEN/KOTA' as title;
SELECT
    COUNT(*) as not_updated_count,
    'Kabupaten/Kota without Kecamatan data' as reason
FROM pdpr_wil_kab
WHERE dpt_l IS NULL
  AND dpt_p IS NULL
  AND total_dpt IS NULL;

-- Distribusi berdasarkan jumlah kecamatan per kabupaten/kota
SELECT 'KECAMATAN COUNT DISTRIBUTION' as title;
SELECT
    kec_count_range,
    COUNT(*) as kabupaten_count,
    FORMAT(AVG(avg_dpt), 2) as avg_dpt_per_kabupaten
FROM (
    SELECT
        kab.id,
        kab.total_dpt as avg_dpt,
        CASE
            WHEN COUNT(kec.id) BETWEEN 1 AND 10 THEN '1-10 Kecamatan'
            WHEN COUNT(kec.id) BETWEEN 11 AND 20 THEN '11-20 Kecamatan'
            WHEN COUNT(kec.id) BETWEEN 21 AND 30 THEN '21-30 Kecamatan'
            WHEN COUNT(kec.id) BETWEEN 31 AND 40 THEN '31-40 Kecamatan'
            WHEN COUNT(kec.id) > 40 THEN '40+ Kecamatan'
        END as kec_count_range
    FROM pdpr_wil_kab kab
    INNER JOIN pdpr_wil_kec kec ON kab.id = kec.kab_id
    WHERE kab.total_dpt IS NOT NULL
    GROUP BY kab.id, kab.total_dpt
) kab_kec_summary
GROUP BY kec_count_range
ORDER BY kabupaten_count DESC;

-- Perbandingan Kota vs Kabupaten
SELECT 'KOTA VS KABUPATEN COMPARISON' as title;
SELECT
    CASE
        WHEN nama LIKE '%KOTA%' OR nama LIKE '%JAKARTA%' THEN 'KOTA'
        ELSE 'KABUPATEN'
    END as type,
    COUNT(*) as jumlah,
    FORMAT(SUM(total_dpt), 0) as total_dpt,
    FORMAT(AVG(total_dpt), 2) as rata_rata_dpt,
    FORMAT(MIN(total_dpt), 0) as min_dpt,
    FORMAT(MAX(total_dpt), 0) as max_dpt
FROM pdpr_wil_kab
WHERE total_dpt IS NOT NULL
GROUP BY type
ORDER BY AVG(total_dpt) DESC;