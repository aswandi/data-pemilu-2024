# 📊 Dokumentasi Update Data DPT (Daftar Pemilih Tetap)

## 🎯 Tujuan
Mengupdate kolom `dpt_l`, `dpt_p`, dan `total_dpt` di tabel `pdpr_wil_tps` dengan data dari tabel `tps` berdasarkan matching `kel_kode` dan `no_tps`.

## 🔄 Proses Update
Data DPT (Daftar Pemilih Tetap) dipindahkan dari tabel `tps` ke `pdpr_wil_tps` dengan kondisi:
- **Matching Key**: `kel_kode` dan `no_tps` harus sama
- **Source**: Tabel `tps` (820,161 records)
- **Target**: Tabel `pdpr_wil_tps` (823,378 records)

## 📊 Hasil Update

### Statistik Berhasil
| Metrik | Nilai | Persentase |
|--------|--------|------------|
| **Total Records pdpr_wil_tps** | 823,378 | 100% |
| **Records Berhasil Update** | 820,161 | 99.61% |
| **Records Tidak Ter-update** | 3,217 | 0.39% |
| **Konsistensi Data** | 100% | ✅ |

### Data DPT Nasional
| Kategori | Jumlah |
|----------|--------|
| **Total DPT Laki-laki** | 101,467,243 pemilih |
| **Total DPT Perempuan** | 101,589,505 pemilih |
| **Total DPT Keseluruhan** | 203,056,748 pemilih |
| **Rata-rata DPT per TPS** | 247.58 pemilih |

### Distribusi DPT per TPS
| Range DPT | Jumlah TPS | Rata-rata |
|-----------|------------|-----------|
| 1-100     | 3,484      | 75.02     |
| 101-200   | 99,753     | 171.94    |
| 201-300   | 716,923    | 258.94    |
| 301-400   | 1          | 302.00    |
| NULL      | 3,217      | -         |

## 🔧 Query SQL yang Digunakan

### Update Statement:
```sql
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
```

### Validasi Data:
- ✅ Semua total_dpt = (dpt_l + dpt_p)
- ✅ Tidak ada inkonsistensi data
- ✅ Timestamp update otomatis

## 🏆 Top 10 TPS dengan DPT Tertinggi
| Kel Kode | No TPS | Nama TPS | DPT L | DPT P | Total DPT |
|----------|--------|----------|-------|-------|-----------|
| 6503012001 | 1 | TPS 001 | 159 | 143 | **302** |
| 1101062009 | 1 | TPS 001 | 151 | 149 | **300** |
| 1101112009 | 3 | TPS 003 | 141 | 159 | **300** |
| 1103102016 | 1 | TPS 001 | 152 | 148 | **300** |
| 1103242001 | 4 | TPS 004 | 158 | 142 | **300** |

## 📁 File Script
- `update_dpt_data.sql` - Script preview dan analisis
- `execute_dpt_update.sql` - Script eksekusi update
- `DPT_UPDATE_DOCUMENTATION.md` - Dokumentasi ini

## ✅ Verifikasi Keamanan
- Update hanya pada records dengan `dpt_l IS NULL` dan `dpt_p IS NULL`
- Validasi data source (`t.dpt_l IS NOT NULL` dan `t.dpt_p IS NOT NULL`)
- Backup implisit melalui timestamp tracking
- Konsistensi 100% pada kalkulasi total_dpt

## 🕒 Waktu Eksekusi
Update berhasil dilakukan pada: **26 September 2025**

## 📈 Analisis
- **Coverage**: 99.61% records berhasil di-update
- **Kualitas Data**: Excellent (100% konsisten)
- **Missing Data**: 3,217 records (0.39%) tidak memiliki pasangan di tabel `tps`
- **Gender Balance**: DPT Perempuan sedikit lebih tinggi (101.6M vs 101.5M)

## 🎉 Status
✅ **COMPLETED SUCCESSFULLY** - 820,161 records berhasil diupdate dengan data DPT lengkap dan konsisten.

---
*Update ini memungkinkan analisis statistik pemilih yang akurat berdasarkan data DPT resmi.*