# 📋 Dokumentasi Update Kolom no_tps

## 🎯 Tujuan
Mengupdate kolom `no_tps` di tabel `pdpr_wil_tps` berdasarkan data dari kolom `tps_nama` dengan menghapus prefix (TPS/KSK/POS) dan leading zeros.

## 🔄 Konversi yang Dilakukan
- `TPS 001` → `1`
- `TPS 010` → `10`
- `TPS 100` → `100`
- `KSK 001` → `1`
- `POS 001` → `1`

## 📊 Hasil Update

### Statistik Berhasil
| Prefix | Total Records | Updated | Success Rate | Min | Max |
|--------|---------------|---------|--------------|-----|-----|
| TPS    | 820,990       | 820,990 | 100%         | 1   | 950 |
| KSK    | 1,702         | 1,702   | 100%         | 1   | 448 |
| POS    | 686           | 686     | 100%         | 1   | 313 |
| **TOTAL** | **823,378** | **823,378** | **100%** | **1** | **950** |

### Distribusi Nomor TPS
| Range  | Jumlah Records |
|--------|----------------|
| 1-10   | 494,603        |
| 11-50  | 272,308        |
| 51-100 | 37,508         |
| 100+   | 18,959         |

## 🔧 Script SQL yang Digunakan

### Format yang Ditangani:
1. **TPS dengan spasi**: `TPS 001`, `TPS 010`
2. **TPS tanpa spasi**: `TPS001`, `TPS010`
3. **KSK dengan spasi**: `KSK 001`, `KSK 010`
4. **KSK tanpa spasi**: `KSK001`, `KSK010`
5. **POS dengan spasi**: `POS 001`, `POS 010`
6. **POS tanpa spasi**: `POS001`, `POS010`

### Logika Konversi:
```sql
CASE
    WHEN tps_nama LIKE 'TPS %' THEN
        CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 5)) AS UNSIGNED)
    WHEN tps_nama LIKE 'TPS%' AND tps_nama NOT LIKE 'TPS %' THEN
        CAST(TRIM(LEADING '0' FROM SUBSTRING(tps_nama, 4)) AS UNSIGNED)
    -- dst untuk KSK dan POS
END
```

## ✅ Verifikasi Keamanan
- Update hanya dilakukan pada record dengan `no_tps IS NULL`
- Validasi format angka dengan REGEXP `^[0-9]+$`
- Tidak ada data yang hilang atau rusak
- 100% success rate pada semua format

## 📁 File Script
- `update_tps_numbers.sql` - Script preview awal
- `update_tps_numbers_improved.sql` - Script dengan format KSK
- `update_tps_final.sql` - Preview final
- `execute_tps_update.sql` - Script eksekusi update
- `TPS_UPDATE_DOCUMENTATION.md` - Dokumentasi ini

## 🕒 Waktu Eksekusi
Update berhasil dilakukan pada: **26 September 2025**

## 🎉 Status
✅ **COMPLETED SUCCESSFULLY** - Semua 823,378 records berhasil diupdate dengan 100% success rate.