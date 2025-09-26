SELECT
    id,
    COALESCE(NULLIF(nama, ''), pro_nama, 'Unknown') AS nama_provinsi,
    COALESCE(jumlah_kecamatan, 0) AS jumlah_kecamatan,
    COALESCE(jumlah_kelurahan, 0) AS jumlah_kelurahan,
    COALESCE(jumlah_tps, 0) AS jumlah_tps,
    COALESCE(total_dpt, 0) AS jumlah_dpt
FROM pdpr_wil_pro
WHERE (nama IS NOT NULL AND nama != '') OR (pro_nama IS NOT NULL AND pro_nama != '')
ORDER BY pro_kode
LIMIT 5;