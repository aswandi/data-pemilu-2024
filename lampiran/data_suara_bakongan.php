<?php
// Halaman Data Suara per Kelurahan di Kecamatan BAKONGAN
// Kabupaten ACEH SELATAN

// Konfigurasi Database
$host = 'localhost';
$username = 'root2';
$password = 'kansas2';
$database = '050_vscode_clacode_laravel11_pemilu2024';

try {
    // Koneksi ke database menggunakan PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query untuk mengambil data kelurahan di kecamatan BAKONGAN dari hr_dpr_ri_kel
    $sql = "
        SELECT 
            kel_nama,
            kec_nama,
            kab_nama,
            chart,
            tbl,
            ts
        FROM hr_dpr_ri_kel 
        WHERE kec_nama = 'BAKONGAN' 
        AND kab_nama = 'ACEH SELATAN'
        ORDER BY kel_nama
    ";
    
    // Query untuk mengambil data suara caleg dari hr_dpr_ri_kec (level kecamatan)
    $sql_kec = "
        SELECT 
            kec_nama,
            kab_nama,
            chart,
            tbl,
            ts
        FROM hr_dpr_ri_kec 
        WHERE kec_nama = 'BAKONGAN' 
        AND kab_nama = 'ACEH SELATAN'
    ";
    
    $stmt_kec = $pdo->prepare($sql_kec);
    $stmt_kec->execute();
    $kecamatan_data = $stmt_kec->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $kelurahan_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Query untuk mengambil data caleg (kandidat legislatif)
    $sql_caleg = "
        SELECT 
            id,
            nama,
            nomor_urut,
            jenis_kelamin,
            partai_id
        FROM dpr_ri_caleg 
        ORDER BY partai_id, nomor_urut
    ";
    
    $stmt_caleg = $pdo->prepare($sql_caleg);
    $stmt_caleg->execute();
    $caleg_data = $stmt_caleg->fetchAll(PDO::FETCH_ASSOC);
    
    // Buat array mapping caleg berdasarkan ID
    $caleg_map = [];
    foreach($caleg_data as $caleg) {
        $caleg_map[$caleg['id']] = $caleg;
    }
    
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk menghitung jumlah TPS dari data JSON
function hitungJumlahTPS($tbl_json) {
    if (empty($tbl_json)) return 0;
    $data = json_decode($tbl_json, true);
    return $data ? count($data) : 0;
}

// Fungsi untuk mengekstrak suara partai dari JSON
function getSuaraPartai($chart_json, $partai_id) {
    if (empty($chart_json)) return 0;
    $data = json_decode($chart_json, true);
    return isset($data[$partai_id]['jml_suara_total']) ? $data[$partai_id]['jml_suara_total'] : 0;
}

// Fungsi untuk mengekstrak suara partai saja (tanpa caleg) dari JSON chart
function getSuaraPartaiSaja($chart_json, $partai_id) {
    if (empty($chart_json)) return 0;
    $data = json_decode($chart_json, true);
    return isset($data[$partai_id]['jml_suara_partai']) ? $data[$partai_id]['jml_suara_partai'] : 0;
}

// Fungsi untuk mengekstrak data suara caleg dari JSON (hr_dpr_ri_kel)
function getSuaraCaleg($tbl_json) {
    if (empty($tbl_json)) return [];
    $data = json_decode($tbl_json, true);
    $result = [];
    
    if ($data && is_array($data)) {
        foreach($data as $tps_code => $tps_data) {
            // Format baru: data caleg langsung di level desa/TPS
            if (is_array($tps_data)) {
                foreach($tps_data as $caleg_id => $suara) {
                    if ($caleg_id !== 'null' && is_numeric($caleg_id)) {
                        if (!isset($result[$caleg_id])) {
                            $result[$caleg_id] = 0;
                        }
                        $result[$caleg_id] += (int)$suara;
                    }
                }
            }
            // Format lama: data caleg dalam nested 'caleg'
            elseif (isset($tps_data['caleg']) && is_array($tps_data['caleg'])) {
                foreach($tps_data['caleg'] as $caleg_id => $suara) {
                    if (!isset($result[$caleg_id])) {
                        $result[$caleg_id] = 0;
                    }
                    $result[$caleg_id] += (int)$suara;
                }
            }
        }
    }
    
    return $result;
}

// Fungsi untuk mengekstrak suara caleg per desa dari JSON
function getSuaraCalegPerDesa($tbl_json, $caleg_id) {
    if (empty($tbl_json)) return [];
    $data = json_decode($tbl_json, true);
    $result = [];
    
    if ($data && is_array($data)) {
        foreach($data as $desa_code => $desa_data) {
            if (is_array($desa_data) && isset($desa_data[$caleg_id])) {
                $result[$desa_code] = (int)$desa_data[$caleg_id];
            }
        }
    }
    
    return $result;
}

// Fungsi untuk mengekstrak data suara caleg dari JSON (hr_dpr_ri_kec)
function getSuaraCalegKec($tbl_json) {
    if (empty($tbl_json)) return [];
    $data = json_decode($tbl_json, true);
    $result = [];
    
    if ($data && is_array($data)) {
        foreach($data as $tps_code => $tps_data) {
            if (is_array($tps_data)) {
                foreach($tps_data as $caleg_id => $suara) {
                    if ($caleg_id !== 'null' && is_numeric($caleg_id)) {
                        if (!isset($result[$caleg_id])) {
                            $result[$caleg_id] = 0;
                        }
                        $result[$caleg_id] += (int)$suara;
                    }
                }
            }
        }
    }
    
    return $result;
}

// Query untuk mengambil semua data partai dari tabel partai
$sql_partai = "SELECT nomor_urut, partai_singkat, nama FROM partai ORDER BY nomor_urut";
$stmt_partai = $pdo->prepare($sql_partai);
$stmt_partai->execute();
$partai_data = $stmt_partai->fetchAll(PDO::FETCH_ASSOC);

// Daftar partai lengkap dari database (18 partai)
$partai_list = [];
foreach($partai_data as $partai) {
    $partai_list[$partai['nomor_urut']] = $partai['partai_singkat'] ?: $partai['nama'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Suara per Kelurahan - Kecamatan BAKONGAN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .info-box {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        .export-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            margin-left: 10px;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .export-btn:hover {
            background-color: #218838;
            color: white;
            text-decoration: none;
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .table-title {
            margin: 0;
            color: #495057;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            flex-grow: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f0f8ff;
        }
        .kelurahan-col {
            text-align: left;
            font-weight: bold;
            background-color: #fff3e0;
        }
        .total-row {
            background-color: #e8f5e8 !important;
            font-weight: bold;
        }
        .partai-header {
            background-color: #e1f5fe;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            min-width: 40px;
            font-size: 12px;
        }
        .number-col {
            width: 50px;
        }
        .kelurahan-name {
            min-width: 200px;
        }
        .tps-col {
            width: 80px;
            background-color: #fff8e1;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 14px;
        }
        .summary-card .number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Data Suara Pemilu DPR RI 2024</h1>
            <p>Per Kelurahan di Kecamatan BAKONGAN, Kabupaten ACEH SELATAN</p>
        </div>

        <?php if (empty($kelurahan_data)): ?>
            <div class="info-box">
                <strong>Informasi:</strong> Data untuk kecamatan BAKONGAN tidak ditemukan dalam database.
            </div>
        <?php else: ?>
            
            <!-- Summary Cards -->
            <div class="summary">
                <div class="summary-card">
                    <h3>Total Kelurahan</h3>
                    <div class="number"><?= count($kelurahan_data) ?></div>
                </div>
                <div class="summary-card">
                    <h3>Total TPS</h3>
                    <div class="number">
                        <?php 
                        $total_tps = 0;
                        foreach($kelurahan_data as $row) {
                            $total_tps += hitungJumlahTPS($row['tbl']);
                        }
                        echo $total_tps;
                        ?>
                    </div>
                </div>
                <div class="summary-card">
                    <h3>Total Suara</h3>
                    <div class="number">
                        <?php 
                        $total_suara = 0;
                        foreach($kelurahan_data as $row) {
                            foreach($partai_list as $id => $nama) {
                                $total_suara += getSuaraPartai($row['chart'], $id);
                            }
                        }
                        echo number_format($total_suara);
                        ?>
                    </div>
                </div>
                <div class="summary-card">
                    <h3>Update Terakhir</h3>
                    <div class="number" style="font-size: 14px;">
                        <?php 
                        $latest_update = '';
                        foreach($kelurahan_data as $row) {
                            if ($row['ts'] > $latest_update) {
                                $latest_update = $row['ts'];
                            }
                        }
                        echo date('d/m/Y H:i', strtotime($latest_update));
                        ?>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Suara -->
            <div class="table-header">
                <h2 class="table-title">📊 Data Suara per Partai</h2>
                <a href="export_excel_partai.php" class="export-btn">📥 Export Excel</a>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th class="number-col">No</th>
                            <th class="kelurahan-name">Nama Kelurahan/Desa</th>
                            <th class="tps-col">Jml TPS</th>
                            <?php foreach($partai_list as $id => $nama_partai): ?>
                                <th class="partai-header"><?= $nama_partai ?></th>
                            <?php endforeach; ?>
                            <th>Total Suara</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $total_per_partai = [];
                        $grand_total = 0;
                        
                        foreach($kelurahan_data as $row): 
                            $jumlah_tps = hitungJumlahTPS($row['tbl']);
                            $total_suara_kelurahan = 0;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="kelurahan-col"><?= htmlspecialchars($row['kel_nama']) ?></td>
                                <td class="tps-col"><?= $jumlah_tps ?></td>
                                <?php 
                                foreach($partai_list as $id => $nama_partai): 
                                    $suara = getSuaraPartai($row['chart'], $id);
                                    $total_suara_kelurahan += $suara;
                                    
                                    if (!isset($total_per_partai[$id])) {
                                        $total_per_partai[$id] = 0;
                                    }
                                    $total_per_partai[$id] += $suara;
                                ?>
                                    <td><?= number_format($suara) ?></td>
                                <?php endforeach; ?>
                                <td><strong><?= number_format($total_suara_kelurahan) ?></strong></td>
                            </tr>
                        <?php 
                            $grand_total += $total_suara_kelurahan;
                        endforeach; 
                        ?>
                        
                        <!-- Baris Total -->
                        <tr class="total-row">
                            <td colspan="2"><strong>TOTAL KECAMATAN</strong></td>
                            <td><strong><?= $total_tps ?></strong></td>
                            <?php foreach($partai_list as $id => $nama_partai): ?>
                                <td><strong><?= number_format($total_per_partai[$id] ?? 0) ?></strong></td>
                            <?php endforeach; ?>
                            <td><strong><?= number_format($grand_total) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabel Data Suara per Caleg -->
            <div style="margin-top: 40px;">
                <div class="table-header">
                    <h2 class="table-title">📊 Data Suara Detail per Caleg (Kandidat Legislatif)</h2>
                    <a href="export_excel_caleg.php" class="export-btn">📥 Export Excel</a>
                </div>
                
                <?php
                // Kumpulkan data suara caleg dari level kecamatan (hr_dpr_ri_kec)
                $all_caleg_votes = [];
                if (!empty($kecamatan_data)) {
                    foreach($kecamatan_data as $row) {
                        $caleg_votes = getSuaraCalegKec($row['tbl']);
                        foreach($caleg_votes as $caleg_id => $suara) {
                            if (!isset($all_caleg_votes[$caleg_id])) {
                                $all_caleg_votes[$caleg_id] = 0;
                            }
                            $all_caleg_votes[$caleg_id] += $suara;
                        }
                    }
                }
                
                // Jika tidak ada data di level kecamatan, coba dari level kelurahan
                if (empty($all_caleg_votes)) {
                    foreach($kelurahan_data as $row) {
                        $caleg_votes = getSuaraCaleg($row['tbl']);
                        foreach($caleg_votes as $caleg_id => $suara) {
                            if (!isset($all_caleg_votes[$caleg_id])) {
                                $all_caleg_votes[$caleg_id] = 0;
                            }
                            $all_caleg_votes[$caleg_id] += $suara;
                        }
                    }
                }
                
                // Filter caleg yang memiliki suara dan urutkan berdasarkan partai dan nomor urut
                 $caleg_with_votes = [];
                 foreach($all_caleg_votes as $caleg_id => $total_suara) {
                     if ($total_suara > 0 && isset($caleg_map[$caleg_id])) {
                         $caleg_info = $caleg_map[$caleg_id];
                         $caleg_info['total_suara'] = $total_suara;
                         $caleg_with_votes[] = $caleg_info;
                     }
                 }
                 
                 // Urutkan berdasarkan partai_id dan nomor urut
                 usort($caleg_with_votes, function($a, $b) {
                     if ($a['partai_id'] == $b['partai_id']) {
                         return $a['nomor_urut'] - $b['nomor_urut'];
                     }
                     return $a['partai_id'] - $b['partai_id'];
                 });
                ?>
                
                <?php if (empty($caleg_with_votes)): ?>
                    <div class="info-box">
                        <strong>Informasi:</strong> Data suara caleg tidak ditemukan atau belum tersedia.
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th class="number-col">No</th>
                                    <th>Partai</th>
                                    <th>No. Urut</th>
                                    <th style="min-width: 250px;">Nama Caleg</th>
                                    <th>L/P</th>
                                    <th>Total Suara</th>
                                    <?php foreach($kelurahan_data as $kel): ?>
                                         <th class="partai-header" style="min-width: 120px; white-space: nowrap;"><?= htmlspecialchars($kel['kel_nama']) ?></th>
                                     <?php endforeach; ?>

                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no_caleg = 1;
                                $current_partai = '';
                                $caleg_count = count($caleg_with_votes);
                                
                                for($i = 0; $i < $caleg_count; $i++):
                                    $caleg = $caleg_with_votes[$i];
                                    $partai_nama = isset($partai_list[$caleg['partai_id']]) ? $partai_list[$caleg['partai_id']] : 'Partai ' . $caleg['partai_id'];
                                    
                                    // Jika partai baru dan caleg nomor urut 1, tampilkan baris suara partai
                                    if ($current_partai != $caleg['partai_id']) {
                                        // Tambahkan separator jika bukan partai pertama
                                        if ($current_partai != '') {
                                            echo '<tr style="height: 5px; background: #f0f0f0;"><td colspan="' . (6 + count($kelurahan_data)) . '"></td></tr>';
                                        }
                                        
                                        // Tampilkan baris suara partai
                                        echo '<tr style="background-color: #fff3e0; font-weight: bold;">';
                                        echo '<td>' . $no_caleg++ . '</td>';
                                        echo '<td style="background-color: #e3f2fd; font-weight: bold;">' . htmlspecialchars($partai_nama) . '</td>';
                                        echo '<td style="text-align: center; background-color: #ffe0b3;">PARTAI</td>';
                                        echo '<td style="text-align: left; padding-left: 10px; font-style: italic;">Suara Partai (tanpa caleg)</td>';
                                        echo '<td style="text-align: center;">-</td>';
                                        
                                        // Hitung total suara partai dari semua kelurahan
                                        $total_suara_partai_all = 0;
                                        foreach($kelurahan_data as $kel) {
                                            $total_suara_partai_all += getSuaraPartaiSaja($kel['chart'], $caleg['partai_id']);
                                        }
                                        echo '<td style="font-weight: bold; background-color: #ffe0b3;">' . number_format($total_suara_partai_all) . '</td>';
                                        
                                        // Tampilkan suara partai per kelurahan
                                        foreach($kelurahan_data as $kel) {
                                            $suara_partai_kel = getSuaraPartaiSaja($kel['chart'], $caleg['partai_id']);
                                            echo '<td style="background-color: #ffe0b3;">' . ($suara_partai_kel > 0 ? number_format($suara_partai_kel) : '-') . '</td>';
                                        }
                                        
                                        echo '</tr>';
                                        
                                        $current_partai = $caleg['partai_id'];
                                    }
                                ?>
                                    <tr>
                                         <td><?= $no_caleg++ ?></td>
                                         <td style="background-color: #e3f2fd; font-weight: bold;"><?= $partai_nama ?></td>
                                         <td style="text-align: center; font-weight: bold; background-color: #fff3e0;"><?= $caleg['nomor_urut'] ?></td>
                                         <td style="text-align: left; padding-left: 10px;"><?= htmlspecialchars($caleg['nama']) ?></td>
                                         <td style="text-align: center;"><?= $caleg['jenis_kelamin'] ?></td>
                                         <td style="font-weight: bold; background-color: #e8f5e8;"><?= number_format($caleg['total_suara']) ?></td>
                                         <?php 
                                         // Tampilkan suara per kelurahan untuk caleg ini
                                         foreach($kelurahan_data as $kel): 
                                             $suara_per_desa = getSuaraCalegPerDesa($kel['tbl'], $caleg['id']);
                                             $total_suara_kel = array_sum($suara_per_desa);
                                         ?>
                                             <td><?= $total_suara_kel > 0 ? number_format($total_suara_kel) : '-' ?></td>
                                         <?php endforeach; ?>
                                         

                                     </tr>
                                <?php 
                                    // Cek apakah ini caleg terakhir dari partai atau caleg terakhir secara keseluruhan
                                    $is_last_caleg_of_party = ($i == $caleg_count - 1) || ($caleg_with_votes[$i + 1]['partai_id'] != $caleg['partai_id']);
                                    
                                    if ($is_last_caleg_of_party) {
                                        // Hitung total suara partai + caleg untuk partai ini
                                        $total_suara_partai_current = 0;
                                        $total_suara_caleg_current = 0;
                                        
                                        // Hitung suara partai
                                        foreach($kelurahan_data as $kel) {
                                            $total_suara_partai_current += getSuaraPartaiSaja($kel['chart'], $caleg['partai_id']);
                                        }
                                        
                                        // Hitung suara caleg untuk partai ini
                                        foreach($caleg_with_votes as $c) {
                                            if ($c['partai_id'] == $caleg['partai_id']) {
                                                $total_suara_caleg_current += $c['total_suara'];
                                            }
                                        }
                                        
                                        $total_gabungan = $total_suara_partai_current + $total_suara_caleg_current;
                                        
                                        // Tampilkan baris total partai + caleg
                                        echo '<tr style="background-color: #e8f5e8; font-weight: bold; border-top: 2px solid #4caf50;">';
                                        echo '<td>' . $no_caleg++ . '</td>';
                                        echo '<td style="background-color: #c8e6c9; font-weight: bold;">' . htmlspecialchars($partai_nama) . '</td>';
                                        echo '<td style="text-align: center; background-color: #a5d6a7;">TOTAL</td>';
                                        echo '<td style="text-align: left; padding-left: 10px; font-style: italic;">Suara Partai + Suara Caleg</td>';
                                        echo '<td style="text-align: center;">-</td>';
                                        echo '<td style="font-weight: bold; background-color: #81c784;">' . number_format($total_gabungan) . '</td>';
                                        
                                        // Tampilkan total per kelurahan (partai + caleg)
                                        foreach($kelurahan_data as $kel) {
                                            $suara_partai_kel = getSuaraPartaiSaja($kel['chart'], $caleg['partai_id']);
                                            $suara_caleg_kel = 0;
                                            
                                            // Hitung suara caleg per kelurahan untuk partai ini
                                            foreach($caleg_with_votes as $c) {
                                                if ($c['partai_id'] == $caleg['partai_id']) {
                                                    $suara_per_desa = getSuaraCalegPerDesa($kel['tbl'], $c['id']);
                                                    $suara_caleg_kel += array_sum($suara_per_desa);
                                                }
                                            }
                                            
                                            $total_kel_gabungan = $suara_partai_kel + $suara_caleg_kel;
                                            echo '<td style="background-color: #a5d6a7;">' . ($total_kel_gabungan > 0 ? number_format($total_kel_gabungan) : '-') . '</td>';
                                        }
                                        
                                        echo '</tr>';
                                    }
                                endfor; ?>
                                
                                <!-- Baris Total Suara Caleg -->
                                 <tr class="total-row">
                                     <td colspan="5"><strong>TOTAL SUARA CALEG</strong></td>
                                     <td><strong><?= number_format(array_sum($all_caleg_votes)) ?></strong></td>
                                     <?php foreach($kelurahan_data as $kel): 
                                         $caleg_votes_kel = getSuaraCaleg($kel['tbl']);
                                         $total_kel = array_sum($caleg_votes_kel);
                                     ?>
                                         <td><strong><?= number_format($total_kel) ?></strong></td>
                                     <?php endforeach; ?>

                                 </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Statistik Caleg -->
                    <div class="summary" style="margin-top: 20px;">
                        <div class="summary-card">
                            <h3>Total Caleg Aktif</h3>
                            <div class="number"><?= count($caleg_with_votes) ?></div>
                        </div>
                        <div class="summary-card">
                            <h3>Partai Terwakili</h3>
                            <div class="number"><?= count(array_unique(array_column($caleg_with_votes, 'partai_id'))) ?></div>
                        </div>
                        <div class="summary-card">
                             <h3>Caleg Perempuan</h3>
                             <div class="number"><?= count(array_filter($caleg_with_votes, function($c) { return $c['jenis_kelamin'] == 'P'; })) ?></div>
                         </div>
                         <div class="summary-card">
                             <h3>Caleg Laki-laki</h3>
                             <div class="number"><?= count(array_filter($caleg_with_votes, function($c) { return $c['jenis_kelamin'] == 'L'; })) ?></div>
                         </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Informasi Tambahan -->
            <div class="info-box">
                <strong>Keterangan:</strong><br>
                • Data partai diambil dari tabel <code>hr_dpr_ri_kel</code> kolom <code>chart</code><br>
                • Data caleg diambil dari tabel <code>hr_dpr_ri_kel</code> kolom <code>tbl</code> dan <code>dpr_ri_caleg</code><br>
                • Jumlah TPS dihitung dari data JSON dalam kolom <code>tbl</code><br>
                • Partai yang ditampilkan: <?= implode(', ', $partai_list) ?><br>
                • Hanya menampilkan caleg yang memperoleh suara > 0
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>Data Pemilu DPR RI 2024 - Sistem Informasi Hasil Pemilu</p>
            <p>Generated on <?= date('d F Y, H:i:s') ?> WIB</p>
        </div>
    </div>

    <script>
        // Auto refresh setiap 5 menit
        setTimeout(function(){
            location.reload();
        }, 300000);
        
        // Highlight row on click
        document.querySelectorAll('tbody tr:not(.total-row)').forEach(row => {
            row.addEventListener('click', function() {
                // Remove previous highlights
                document.querySelectorAll('tbody tr').forEach(r => r.style.backgroundColor = '');
                // Highlight clicked row
                this.style.backgroundColor = '#fff3cd';
            });
        });
    </script>
</body>
</html>