# 📊 Dokumentasi Update Data DPT Kelurahan

## 🎯 Tujuan
Mengupdate kolom `dpt_l`, `dpt_p`, dan `total_dpt` di tabel `pdpr_wil_kel` dengan data agregasi dari tabel `pdpr_wil_tps` berdasarkan `kel_id`.

## 🔄 Proses Agregasi
Data DPT diagregasi dari level TPS ke level Kelurahan dengan:
- **Agregasi Function**: `SUM()` berdasarkan `kel_id`
- **Source**: Tabel `pdpr_wil_tps` (820,161 TPS records)
- **Target**: Tabel `pdpr_wil_kel` (83,860 kelurahan records)

## 📊 Hasil Update

### Statistik Berhasil
| Metrik | Nilai | Persentase |
|--------|--------|------------|
| **Total Kelurahan** | 83,860 | 100% |
| **Kelurahan Berhasil Update** | 83,731 | 99.85% |
| **Kelurahan Tidak Ter-update** | 129 | 0.15% |
| **Konsistensi Data** | 100% | ✅ |

### Data DPT Nasional (Level Kelurahan)
| Kategori | Jumlah |
|----------|--------|
| **Total DPT Laki-laki** | 101,467,243 pemilih |
| **Total DPT Perempuan** | 101,589,505 pemilih |
| **Total DPT Keseluruhan** | 203,056,748 pemilih |
| **Rata-rata DPT per Kelurahan** | 2,425.11 pemilih |

### Distribusi DPT per Kelurahan
| Range DPT | Jumlah Kelurahan | Rata-rata DPT |
|-----------|------------------|---------------|
| 1-500     | 18,416          | 297.86        |
| 501-1,000 | 16,413          | 726.44        |
| 1,001-2,000 | 17,711        | 1,449.99      |
| 2,001-3,000 | 10,646        | 2,465.72      |
| 3,001-5,000 | 11,140        | 3,840.03      |
| 5,000+    | 9,405           | 9,669.26      |
| NULL      | 129             | -             |

## 🏆 Top 10 Provinsi dengan DPT Tertinggi
| Provinsi | Jumlah Kelurahan | Total DPT | Rata-rata per Kel |
|----------|------------------|-----------|-------------------|
| **Jawa Barat** | 5,957 | 35,714,901 | 5,995.45 |
| **Jawa Timur** | 8,494 | 31,402,838 | 3,697.06 |
| **Jawa Tengah** | 8,563 | 28,289,413 | 3,303.68 |
| **Sumatera Utara** | 6,110 | 10,853,940 | 1,776.42 |
| **Banten** | 1,552 | 8,842,646 | 5,697.58 |
| **DKI Jakarta** | 267 | 8,252,897 | 30,909.73 |
| **Sulawesi Selatan** | 3,059 | 6,670,582 | 2,180.64 |
| **Lampung** | 2,651 | 6,539,128 | 2,466.66 |
| **Sumatera Selatan** | 3,249 | 6,326,348 | 1,947.17 |
| **Riau** | 1,862 | 4,732,174 | 2,541.45 |

## 🏅 Top 10 Kelurahan dengan DPT Tertinggi
| Kelurahan | Kecamatan | Kabupaten/Kota | Provinsi | Total DPT |
|-----------|-----------|----------------|----------|-----------|
| **Kapuk** | Cengkareng | Jakarta Barat | DKI Jakarta | **124,415** |
| **Penggilingan** | Cakung | Jakarta Timur | DKI Jakarta | **94,260** |
| **Pulo Gebang** | Cakung | Jakarta Timur | DKI Jakarta | **91,607** |
| **Penjaringan** | Penjaringan | Jakarta Utara | DKI Jakarta | **82,177** |
| **Tegal Alur** | Kalideres | Jakarta Barat | DKI Jakarta | **77,912** |
| **Jatinegara** | Cakung | Jakarta Timur | DKI Jakarta | **77,189** |
| **Wanasari** | Cibitung | Bekasi | Jawa Barat | **75,700** |
| **Cengkareng Timur** | Cengkareng | Jakarta Barat | DKI Jakarta | **75,212** |
| **Duri Kosambi** | Cengkareng | Jakarta Barat | DKI Jakarta | **74,024** |
| **Pegadungan** | Kalideres | Jakarta Barat | DKI Jakarta | **71,543** |

## 📈 Distribusi Berdasarkan Jumlah TPS
| Range TPS | Jumlah Kelurahan | Rata-rata DPT per Kelurahan |
|-----------|------------------|----------------------------|
| 2-5 TPS   | 33,173          | 718.27                     |
| 6-10 TPS  | 17,918          | 1,897.79                   |
| 11-20 TPS | 15,353          | 3,634.86                   |
| 20+ TPS   | 8,853           | 9,920.64                   |
| 1 TPS     | 8,434           | 188.72                     |

## 🔧 Query SQL yang Digunakan

### Update Statement:
```sql
UPDATE pdpr_wil_kel k
INNER JOIN (
    SELECT kel_id,
           SUM(dpt_l) as sum_dpt_l,
           SUM(dpt_p) as sum_dpt_p,
           SUM(total_dpt) as sum_total_dpt
    FROM pdpr_wil_tps
    WHERE dpt_l IS NOT NULL
      AND dpt_p IS NOT NULL
      AND total_dpt IS NOT NULL
    GROUP BY kel_id
) tps_summary ON k.id = tps_summary.kel_id
SET
    k.dpt_l = tps_summary.sum_dpt_l,
    k.dpt_p = tps_summary.sum_dpt_p,
    k.total_dpt = tps_summary.sum_total_dpt,
    k.updated_at = CURRENT_TIMESTAMP
WHERE k.dpt_l IS NULL
  AND k.dpt_p IS NULL
  AND k.total_dpt IS NULL;
```

## ✅ Verifikasi Data
- ✅ **Total DPT Match**: 203,056,748 (kelurahan = TPS)
- ✅ **Konsistensi 100%**: Semua agregasi benar
- ✅ **No Data Loss**: Tidak ada kehilangan data
- ✅ **Timestamp Tracking**: Update otomatis

## 📁 File Script
- `update_kel_dpt_data.sql` - Script preview dan analisis
- `execute_kel_dpt_update.sql` - Script eksekusi update
- `KELURAHAN_DPT_UPDATE_DOCUMENTATION.md` - Dokumentasi ini

## 🕒 Waktu Eksekusi
Update berhasil dilakukan pada: **26 September 2025**

## 📊 Insights & Analisis

### Pola Demografis:
1. **DKI Jakarta** memiliki rata-rata DPT tertinggi per kelurahan (30,909.73)
2. **Kelurahan urban** cenderung memiliki DPT lebih tinggi
3. **Mayoritas kelurahan** (33,173) memiliki 2-5 TPS
4. **Gender balance**: Perempuan sedikit lebih banyak (+122,262)

### Distribusi Regional:
- **Jawa**: Dominan dengan 3 provinsi teratas
- **Urban areas**: Konsentrasi DPT tinggi
- **Rural areas**: DPT lebih rendah tapi tersebar luas

## 🎉 Status
✅ **COMPLETED SUCCESSFULLY** - 83,731 kelurahan berhasil diupdate dengan data DPT agregat yang konsisten dan akurat.

---
*Data level kelurahan ini memungkinkan analisis demografis dan perencanaan logistik pemilu yang lebih efektif.*