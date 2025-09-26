SELECT
    id,
    COALESCE(NULLIF(nama, ''), kab_nama, 'Unknown') AS nama_kabkota,
    COALESCE(jumlah_kecamatan, 0) AS jumlah_kecamatan,
    COALESCE(jumlah_kelurahan, 0) AS jumlah_kelurahan,
    COALESCE(jumlah_tps, 0) AS jumlah_tps,
    COALESCE(total_dpt, 0) AS jumlah_dpt
FROM pdpr_wil_kab
WHERE pro_id = 191087 AND ((nama IS NOT NULL AND nama != '') OR (kab_nama IS NOT NULL AND kab_nama != ''))
ORDER BY nama_kabkota
LIMIT 5;