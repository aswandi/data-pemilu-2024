-- ============================================================================
-- EXECUTE KECAMATAN DPT DATA UPDATE
-- Script untuk mengupdate kolom dpt_l, dpt_p, dan total_dpt
-- di tabel pdpr_wil_kec dari agregasi data tabel pdpr_wil_kel
-- Agregasi berdasarkan kec_id (SUM data per kecamatan)
-- ============================================================================

-- UPDATE data DPT dari agregasi tabel pdpr_wil_kel ke pdpr_wil_kec
UPDATE pdpr_wil_kec kec
INNER JOIN (
    SELECT kec_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt,
           COUNT(*) as kel_count
    FROM pdpr_wil_kel
    WHERE dpt_l IS NOT NULL
      AND dpt_p IS NOT NULL
      AND total_dpt IS NOT NULL
    GROUP BY kec_id
) kel_summary ON kec.id = kel_summary.kec_id
SET
    kec.dpt_l = kel_summary.sum_dpt_l,
    kec.dpt_p = kel_summary.sum_dpt_p,
    kec.total_dpt = kel_summary.sum_total_dpt,
    kec.updated_at = CURRENT_TIMESTAMP
WHERE kec.dpt_l IS NULL
  AND kec.dpt_p IS NULL
  AND kec.total_dpt IS NULL;

-- VERIFIKASI HASIL UPDATE
SELECT 'UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_kecamatan,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt,
    ROUND((COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage
FROM pdpr_wil_kec;

-- Statistik agregat DPT berdasarkan range per kecamatan
SELECT 'DPT DISTRIBUTION PER KECAMATAN' as title;
SELECT
    CASE
        WHEN total_dpt IS NULL THEN 'NULL'
        WHEN total_dpt BETWEEN 1 AND 5000 THEN '1-5,000'
        WHEN total_dpt BETWEEN 5001 AND 10000 THEN '5,001-10,000'
        WHEN total_dpt BETWEEN 10001 AND 20000 THEN '10,001-20,000'
        WHEN total_dpt BETWEEN 20001 AND 30000 THEN '20,001-30,000'
        WHEN total_dpt BETWEEN 30001 AND 50000 THEN '30,001-50,000'
        WHEN total_dpt > 50000 THEN '50,000+'
    END as dpt_range,
    COUNT(*) as kecamatan_count,
    ROUND(AVG(total_dpt), 2) as avg_dpt,
    MIN(total_dpt) as min_dpt,
    MAX(total_dpt) as max_dpt
FROM pdpr_wil_kec
GROUP BY dpt_range
ORDER BY dpt_range;

-- Total DPT nasional dari level kecamatan
SELECT 'NATIONAL DPT SUMMARY FROM KECAMATAN LEVEL' as title;
SELECT
    FORMAT(SUM(dpt_l), 0) as total_dpt_laki_laki,
    FORMAT(SUM(dpt_p), 0) as total_dpt_perempuan,
    FORMAT(SUM(total_dpt), 0) as total_dpt_nasional,
    FORMAT(AVG(total_dpt), 2) as rata_rata_dpt_per_kecamatan
FROM pdpr_wil_kec
WHERE total_dpt IS NOT NULL;

-- Sample hasil update per kabupaten/kota
SELECT 'SAMPLE BY KABUPATEN/KOTA' as title;
SELECT
    kab.nama as nama_kabupaten,
    COUNT(kec.id) as jumlah_kecamatan,
    FORMAT(SUM(kec.total_dpt), 0) as total_dpt_kabupaten,
    FORMAT(AVG(kec.total_dpt), 2) as rata_rata_dpt_per_kec
FROM pdpr_wil_kec kec
INNER JOIN pdpr_wil_kab kab ON kec.kab_id = kab.id
WHERE kec.total_dpt IS NOT NULL
GROUP BY kab.id, kab.nama
ORDER BY SUM(kec.total_dpt) DESC
LIMIT 10;

-- Top 10 kecamatan dengan DPT tertinggi
SELECT 'TOP 10 KECAMATAN HIGHEST DPT' as title;
SELECT
    kec.nama as nama_kecamatan,
    kab.nama as nama_kabupaten,
    pro.nama as nama_provinsi,
    FORMAT(kec.dpt_l, 0) as dpt_l,
    FORMAT(kec.dpt_p, 0) as dpt_p,
    FORMAT(kec.total_dpt, 0) as total_dpt
FROM pdpr_wil_kec kec
LEFT JOIN pdpr_wil_kab kab ON kec.kab_id = kab.id
LEFT JOIN pdpr_wil_pro pro ON kec.pro_id = pro.id
WHERE kec.total_dpt IS NOT NULL
ORDER BY kec.total_dpt DESC
LIMIT 10;

-- Verifikasi konsistensi dengan data kelurahan
SELECT 'CONSISTENCY CHECK WITH KELURAHAN DATA' as title;
SELECT
    COUNT(*) as kecamatan_updated,
    COUNT(CASE WHEN kec.total_dpt = kel_sum.calculated_total THEN 1 END) as consistent_totals,
    COUNT(CASE WHEN kec.total_dpt != kel_sum.calculated_total THEN 1 END) as inconsistent_totals
FROM pdpr_wil_kec kec
INNER JOIN (
    SELECT kec_id, SUM(total_dpt) as calculated_total
    FROM pdpr_wil_kel
    WHERE total_dpt IS NOT NULL
    GROUP BY kec_id
) kel_sum ON kec.id = kel_sum.kec_id
WHERE kec.total_dpt IS NOT NULL;

-- Kecamatan yang tidak ter-update
SELECT 'NOT UPDATED KECAMATAN' as title;
SELECT
    COUNT(*) as not_updated_count,
    'Kecamatan without Kelurahan data' as reason
FROM pdpr_wil_kec
WHERE dpt_l IS NULL
  AND dpt_p IS NULL
  AND total_dpt IS NULL;

-- Distribusi berdasarkan jumlah kelurahan per kecamatan
SELECT 'KELURAHAN COUNT DISTRIBUTION' as title;
SELECT
    kel_count_range,
    COUNT(*) as kecamatan_count,
    FORMAT(AVG(avg_dpt), 2) as avg_dpt_per_kecamatan
FROM (
    SELECT
        kec.id,
        kec.total_dpt as avg_dpt,
        CASE
            WHEN COUNT(kel.id) BETWEEN 1 AND 5 THEN '1-5 Kelurahan'
            WHEN COUNT(kel.id) BETWEEN 6 AND 10 THEN '6-10 Kelurahan'
            WHEN COUNT(kel.id) BETWEEN 11 AND 15 THEN '11-15 Kelurahan'
            WHEN COUNT(kel.id) BETWEEN 16 AND 20 THEN '16-20 Kelurahan'
            WHEN COUNT(kel.id) > 20 THEN '20+ Kelurahan'
        END as kel_count_range
    FROM pdpr_wil_kec kec
    INNER JOIN pdpr_wil_kel kel ON kec.id = kel.kec_id
    WHERE kec.total_dpt IS NOT NULL
    GROUP BY kec.id, kec.total_dpt
) kec_kel_summary
GROUP BY kel_count_range
ORDER BY kecamatan_count DESC;