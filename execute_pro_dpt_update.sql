-- ============================================================================
-- EXECUTE PROVINSI DPT DATA UPDATE
-- Script untuk mengupdate kolom dpt_l, dpt_p, dan total_dpt
-- di tabel pdpr_wil_pro dari agregasi data tabel pdpr_wil_kab
-- Agregasi berdasarkan pro_id (SUM data per provinsi)
-- ============================================================================

-- UPDATE data DPT dari agregasi tabel pdpr_wil_kab ke pdpr_wil_pro
UPDATE pdpr_wil_pro pro
INNER JOIN (
    SELECT pro_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt,
           COUNT(*) as kab_count
    FROM pdpr_wil_kab
    WHERE dpt_l IS NOT NULL
      AND dpt_p IS NOT NULL
      AND total_dpt IS NOT NULL
    GROUP BY pro_id
) kab_summary ON pro.id = kab_summary.pro_id
SET
    pro.dpt_l = kab_summary.sum_dpt_l,
    pro.dpt_p = kab_summary.sum_dpt_p,
    pro.total_dpt = kab_summary.sum_total_dpt,
    pro.updated_at = CURRENT_TIMESTAMP
WHERE pro.dpt_l IS NULL
  AND pro.dpt_p IS NULL
  AND pro.total_dpt IS NULL;

-- VERIFIKASI HASIL UPDATE
SELECT 'UPDATE COMPLETED - VERIFICATION' as status;

-- Statistik setelah update
SELECT 'AFTER UPDATE STATISTICS' as title;
SELECT
    COUNT(*) as total_provinsi,
    COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) as filled_dpt_l,
    COUNT(CASE WHEN dpt_p IS NOT NULL THEN 1 END) as filled_dpt_p,
    COUNT(CASE WHEN total_dpt IS NOT NULL THEN 1 END) as filled_total_dpt,
    ROUND((COUNT(CASE WHEN dpt_l IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 2) as success_percentage
FROM pdpr_wil_pro;

-- Statistik agregat DPT berdasarkan range per provinsi
SELECT 'DPT DISTRIBUTION PER PROVINSI' as title;
SELECT
    CASE
        WHEN total_dpt IS NULL THEN 'NULL'
        WHEN total_dpt BETWEEN 1 AND 1000000 THEN '1-1M'
        WHEN total_dpt BETWEEN 1000001 AND 5000000 THEN '1M-5M'
        WHEN total_dpt BETWEEN 5000001 AND 10000000 THEN '5M-10M'
        WHEN total_dpt BETWEEN 10000001 AND 20000000 THEN '10M-20M'
        WHEN total_dpt BETWEEN 20000001 AND 30000000 THEN '20M-30M'
        WHEN total_dpt > 30000000 THEN '30M+'
    END as dpt_range,
    COUNT(*) as provinsi_count,
    ROUND(AVG(total_dpt), 2) as avg_dpt,
    MIN(total_dpt) as min_dpt,
    MAX(total_dpt) as max_dpt
FROM pdpr_wil_pro
GROUP BY dpt_range
ORDER BY dpt_range;

-- Total DPT nasional dari level provinsi
SELECT 'NATIONAL DPT SUMMARY FROM PROVINSI LEVEL' as title;
SELECT
    FORMAT(SUM(dpt_l), 0) as total_dpt_laki_laki,
    FORMAT(SUM(dpt_p), 0) as total_dpt_perempuan,
    FORMAT(SUM(total_dpt), 0) as total_dpt_nasional,
    FORMAT(AVG(total_dpt), 2) as rata_rata_dpt_per_provinsi
FROM pdpr_wil_pro
WHERE total_dpt IS NOT NULL;

-- Top 10 provinsi dengan DPT tertinggi
SELECT 'TOP 10 PROVINSI HIGHEST DPT' as title;
SELECT
    pro.nama as nama_provinsi,
    FORMAT(pro.dpt_l, 0) as dpt_l,
    FORMAT(pro.dpt_p, 0) as dpt_p,
    FORMAT(pro.total_dpt, 0) as total_dpt,
    (SELECT COUNT(*) FROM pdpr_wil_kab WHERE pro_id = pro.id AND total_dpt IS NOT NULL) as jumlah_kab_kota_active
FROM pdpr_wil_pro pro
WHERE pro.total_dpt IS NOT NULL
ORDER BY pro.total_dpt DESC
LIMIT 10;

-- Verifikasi konsistensi dengan data kabupaten/kota
SELECT 'CONSISTENCY CHECK WITH KABUPATEN DATA' as title;
SELECT
    COUNT(*) as provinsi_updated,
    COUNT(CASE WHEN pro.total_dpt = kab_sum.calculated_total THEN 1 END) as consistent_totals,
    COUNT(CASE WHEN pro.total_dpt != kab_sum.calculated_total THEN 1 END) as inconsistent_totals
FROM pdpr_wil_pro pro
INNER JOIN (
    SELECT pro_id, SUM(total_dpt) as calculated_total
    FROM pdpr_wil_kab
    WHERE total_dpt IS NOT NULL
    GROUP BY pro_id
) kab_sum ON pro.id = kab_sum.pro_id
WHERE pro.total_dpt IS NOT NULL;

-- Provinsi yang tidak ter-update
SELECT 'NOT UPDATED PROVINSI' as title;
SELECT
    COUNT(*) as not_updated_count,
    'Provinsi without Kabupaten/Kota data' as reason
FROM pdpr_wil_pro
WHERE dpt_l IS NULL
  AND dpt_p IS NULL
  AND total_dpt IS NULL;

-- Distribusi berdasarkan jumlah kabupaten/kota per provinsi
SELECT 'KABUPATEN COUNT DISTRIBUTION' as title;
SELECT
    kab_count_range,
    COUNT(*) as provinsi_count,
    FORMAT(AVG(avg_dpt), 2) as avg_dpt_per_provinsi
FROM (
    SELECT
        pro.id,
        pro.total_dpt as avg_dpt,
        CASE
            WHEN COUNT(kab.id) BETWEEN 1 AND 10 THEN '1-10 Kab/Kota'
            WHEN COUNT(kab.id) BETWEEN 11 AND 20 THEN '11-20 Kab/Kota'
            WHEN COUNT(kab.id) BETWEEN 21 AND 30 THEN '21-30 Kab/Kota'
            WHEN COUNT(kab.id) BETWEEN 31 AND 40 THEN '31-40 Kab/Kota'
            WHEN COUNT(kab.id) > 40 THEN '40+ Kab/Kota'
        END as kab_count_range
    FROM pdpr_wil_pro pro
    INNER JOIN pdpr_wil_kab kab ON pro.id = kab.pro_id
    WHERE pro.total_dpt IS NOT NULL
    GROUP BY pro.id, pro.total_dpt
) pro_kab_summary
GROUP BY kab_count_range
ORDER BY provinsi_count DESC;

-- Regional comparison (Pulau besar)
SELECT 'REGIONAL COMPARISON' as title;
SELECT
    CASE
        WHEN nama LIKE '%JAWA%' THEN 'JAWA'
        WHEN nama LIKE '%SUMATERA%' THEN 'SUMATERA'
        WHEN nama LIKE '%KALIMANTAN%' THEN 'KALIMANTAN'
        WHEN nama LIKE '%SULAWESI%' THEN 'SULAWESI'
        WHEN nama LIKE '%BALI%' OR nama LIKE '%NUSA%' THEN 'BALI & NUSA TENGGARA'
        WHEN nama LIKE '%MALUKU%' OR nama LIKE '%PAPUA%' THEN 'MALUKU & PAPUA'
        ELSE 'LAINNYA'
    END as region,
    COUNT(*) as jumlah_provinsi,
    FORMAT(SUM(total_dpt), 0) as total_dpt_regional,
    FORMAT(AVG(total_dpt), 2) as rata_rata_dpt_per_provinsi
FROM pdpr_wil_pro
WHERE total_dpt IS NOT NULL
GROUP BY region
ORDER BY SUM(total_dpt) DESC;