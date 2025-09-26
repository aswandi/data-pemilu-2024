SELECT
    COUNT(*) as total_provinces,
    SUM(jumlah_kecamatan) as total_kecamatan,
    SUM(jumlah_kelurahan) as total_kelurahan,
    SUM(jumlah_tps) as total_tps,
    SUM(total_dpt) as total_dpt
FROM pdpr_wil_pro
WHERE (nama IS NOT NULL AND nama != '') OR (pro_nama IS NOT NULL AND pro_nama != '');