<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProvinceController;

Route::get('/test', function () {
    return 'Laravel is working!';
});

Route::get('/test-db', function () {
    try {
        $count = \App\Models\Province::count();
        return "Database connection works! Found {$count} provinces.";
    } catch (\Exception $e) {
        return "Database error: " . $e->getMessage();
    }
});

Route::get('/test-query', function () {
    try {
        $provinces = \App\Models\Province::getProvinceDataWithStats();
        return "Query works! Found " . count($provinces) . " provinces with stats.";
    } catch (\Exception $e) {
        return "Query error: " . $e->getMessage();
    }
});

Route::get('/', function () {
    return redirect('/dpr/provinsi');
});
Route::get('/dpr/provinsi', [ProvinceController::class, 'index'])->name('dpr.provinsi');
Route::get('/dpr/provinsi/{provinceId}/dapil', [ProvinceController::class, 'dapil'])->name('dpr.dapil');
Route::get('/dpr/provinsi/{provinceId}/kabupaten', [ProvinceController::class, 'kabupaten'])->name('dpr.kabupaten');
Route::get('/dpr/kabupaten/{kabupatenId}/kecamatan', [ProvinceController::class, 'kecamatan'])->name('dpr.kecamatan');
Route::get('/dpr/kabupaten/{kabupatenId}/suara', [ProvinceController::class, 'suaraKabupaten'])->name('dpr.suara.kabupaten');
Route::get('/dpr/kabupaten/{kabupatenId}/suara/export-excel', [ProvinceController::class, 'exportSuaraExcel'])->name('dpr.suara.export.excel');
Route::get('/dpr/kabupaten/{kabupatenId}/suara/caleg', [ProvinceController::class, 'suaraCalegKabupaten'])->name('dpr.suara.caleg.kabupaten');
Route::get('/dpr/kabupaten/{kabupatenId}/suara/caleg/export-excel', [ProvinceController::class, 'exportKabupatenCalegExcel'])->name('dpr.suara.caleg.kabupaten.export.excel');
Route::get('/dpr/kelurahan/{kelurahanId}/caleg', [ProvinceController::class, 'suaraCaleg'])->name('dpr.suara.caleg');
Route::get('/dpr/kecamatan/{kecamatanId}/kelurahan', [ProvinceController::class, 'kelurahan'])->name('dpr.kelurahan');
Route::get('/dpr/kecamatan/{kecamatanId}/suara', [ProvinceController::class, 'suaraKecamatan'])->name('dpr.suara.kecamatan');
Route::get('/dpr/kecamatan/{kecamatanId}/tps', [ProvinceController::class, 'tpsKecamatan'])->name('dpr.tps.kecamatan');
Route::get('/dpr/kecamatan/{kecamatanId}/suara/export-excel', [ProvinceController::class, 'exportKecamatanSuaraExcel'])->name('dpr.suara.kecamatan.export.excel');
Route::get('/dpr/kecamatan/{kecamatanId}/caleg-dapil/export-excel', [ProvinceController::class, 'exportKecamatanCalegByDapil'])->name('dpr.kecamatan.caleg.dapil.export.excel');
Route::get('/dpr/kecamatan/{kecamatanId}/export-tps-excel', [ProvinceController::class, 'exportTpsExcel'])->name('dpr.tps.export.excel');
Route::get('/changelog', [ProvinceController::class, 'changelog'])->name('changelog');
