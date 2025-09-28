<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Dapil;
use App\Models\VoteData;
use App\Exports\VoteDataExport;
use App\Exports\KecamatanVoteDataExport;
use App\Exports\KecamatanMultiSheetExport;
use App\Exports\KecamatanWebFormatExport;
use App\Exports\KecamatanCalegByDapilExport;
use App\Exports\TpsMultiSheetExport;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProvinceController extends Controller
{
    public function index()
    {
        try {
            $provinces = Province::getProvinceDataWithStats();
            $statistics = Province::getRealStatistics();

            return Inertia::render('Provinces/Index', [
                'provinces' => $provinces,
                'statistics' => $statistics,
                'title' => 'Data Wilayah Indonesia - Provinsi'
            ]);
        } catch (\Exception $e) {
            // Fallback data if database connection fails
            $provinces = [
                (object)[
                    'id' => 1,
                    'nama_provinsi' => 'Koneksi Database Error',
                    'jumlah_dapil' => 0,
                    'jumlah_kabkota' => 0,
                    'jumlah_kecamatan' => 0,
                    'jumlah_kelurahan' => 0,
                    'jumlah_tps' => 0,
                    'jumlah_dpt' => 0
                ]
            ];

            return Inertia::render('Provinces/Index', [
                'provinces' => $provinces,
                'title' => 'Data Wilayah Indonesia - Provinsi',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function dapil($provinceId)
    {
        try {
            $dapils = Dapil::getDapilDataByProvince($provinceId);
            $provinceData = Dapil::getProvinceDataWithKabupatenCount($provinceId);

            return Inertia::render('Dapil/Index', [
                'dapils' => $dapils,
                'provinceName' => $provinceData->nama_provinsi ?? 'Unknown Province',
                'jumlahKabkota' => $provinceData->jumlah_kabkota ?? 0,
                'provinceId' => $provinceId,
                'title' => "Data Dapil - {$provinceData->nama_provinsi}"
            ]);
        } catch (\Exception $e) {
            // Fallback data if database connection fails
            $dapils = [
                (object)[
                    'id' => 1,
                    'nama_dapil' => 'Koneksi Database Error',
                    'dapil_kode' => 'ERROR',
                    'nama_provinsi' => 'Error'
                ]
            ];

            return Inertia::render('Dapil/Index', [
                'dapils' => $dapils,
                'provinceName' => 'Error',
                'jumlahKabkota' => 0,
                'provinceId' => $provinceId,
                'title' => 'Data Dapil - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function kabupaten($provinceId)
    {
        try {
            $kabupaten = Province::getKabupatenDataWithStats($provinceId);
            $provinceName = Province::getProvinceName($provinceId);

            return Inertia::render('Kabupaten/Index', [
                'kabupaten' => $kabupaten,
                'provinceName' => $provinceName,
                'provinceId' => $provinceId,
                'title' => "Data Kabupaten/Kota - {$provinceName}"
            ]);
        } catch (\Exception $e) {
            // Fallback data if database connection fails
            $kabupaten = [
                (object)[
                    'id' => 1,
                    'nama_kabkota' => 'Koneksi Database Error',
                    'jumlah_kecamatan' => 0,
                    'jumlah_kelurahan' => 0,
                    'jumlah_tps' => 0,
                    'jumlah_dpt' => 0
                ]
            ];

            return Inertia::render('Kabupaten/Index', [
                'kabupaten' => $kabupaten,
                'provinceName' => 'Error',
                'provinceId' => $provinceId,
                'title' => 'Data Kabupaten/Kota - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function kecamatan($kabupatenId)
    {
        try {
            $kecamatan = Province::getKecamatanDataWithStats($kabupatenId);
            $kabupatenInfo = Province::getKabupatenInfo($kabupatenId);

            return Inertia::render('Kecamatan/Index', [
                'kecamatan' => $kecamatan,
                'kabupatenName' => $kabupatenInfo['kabupaten_name'],
                'provinceName' => $kabupatenInfo['province_name'],
                'provinceId' => $kabupatenInfo['province_id'],
                'kabupatenId' => $kabupatenId,
                'title' => "Data Kecamatan - {$kabupatenInfo['kabupaten_name']}"
            ]);
        } catch (\Exception $e) {
            // Fallback data if database connection fails
            $kecamatan = [
                (object)[
                    'id' => 1,
                    'nama_kecamatan' => 'Koneksi Database Error',
                    'jumlah_kelurahan' => 0,
                    'jumlah_tps' => 0
                ]
            ];

            return Inertia::render('Kecamatan/Index', [
                'kecamatan' => $kecamatan,
                'kabupatenName' => 'Error',
                'provinceName' => 'Error',
                'provinceId' => 0,
                'kabupatenId' => $kabupatenId,
                'title' => 'Data Kecamatan - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function kelurahan($kecamatanId)
    {
        try {
            $kelurahan = Province::getKelurahanDataWithStats($kecamatanId);
            $kecamatanInfo = Province::getKecamatanInfo($kecamatanId);

            return Inertia::render('Kelurahan/Index', [
                'kelurahan' => $kelurahan,
                'kecamatanName' => $kecamatanInfo['kecamatan_name'],
                'kabupatenName' => $kecamatanInfo['kabupaten_name'],
                'provinceName' => $kecamatanInfo['province_name'],
                'provinceId' => $kecamatanInfo['province_id'],
                'kabupatenId' => $kecamatanInfo['kabupaten_id'],
                'kecamatanId' => $kecamatanId,
                'title' => "Data Kelurahan/Desa - {$kecamatanInfo['kecamatan_name']}"
            ]);
        } catch (\Exception $e) {
            // Fallback data if database connection fails
            $kelurahan = [
                (object)[
                    'id' => 1,
                    'nama_kelurahan' => 'Koneksi Database Error',
                    'jumlah_tps' => 0
                ]
            ];

            return Inertia::render('Kelurahan/Index', [
                'kelurahan' => $kelurahan,
                'kecamatanName' => 'Error',
                'kabupatenName' => 'Error',
                'provinceName' => 'Error',
                'provinceId' => 0,
                'kabupatenId' => 0,
                'kecamatanId' => $kecamatanId,
                'title' => 'Data Kelurahan/Desa - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function suaraKabupaten($kabupatenId)
    {
        try {
            // Get kabupaten info
            $kabupatenInfo = Province::getKabupatenInfo($kabupatenId);

            // Get vote data for the kabupaten
            $voteData = VoteData::getVoteDataByKabupaten($kabupatenId);

            // Get party data
            $parties = VoteData::getPartyData();

            return Inertia::render('Suara/KabupatenIndex', [
                'voteData' => $voteData,
                'parties' => $parties,
                'kabupatenName' => $kabupatenInfo['kabupaten_name'],
                'provinceName' => $kabupatenInfo['province_name'],
                'provinceId' => $kabupatenInfo['province_id'],
                'kabupatenId' => $kabupatenId,
                'title' => "Data Suara DPR RI - {$kabupatenInfo['kabupaten_name']}"
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Suara/KabupatenIndex', [
                'voteData' => [],
                'parties' => [],
                'kabupatenName' => 'Error',
                'provinceName' => 'Error',
                'provinceId' => 0,
                'kabupatenId' => $kabupatenId,
                'title' => 'Data Suara DPR RI - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function exportSuaraExcel($kabupatenId)
    {
        try {
            // Get kabupaten info
            $kabupatenInfo = Province::getKabupatenInfo($kabupatenId);

            $fileName = 'Data_Suara_DPR_RI_' . str_replace(' ', '_', $kabupatenInfo['kabupaten_name']) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new VoteDataExport($kabupatenId, $kabupatenInfo['kabupaten_name'], $kabupatenInfo['province_name']),
                $fileName
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function suaraCalegKabupaten($kabupatenId)
    {
        try {
            // Get kabupaten info
            $kabupatenInfo = Province::getKabupatenInfo($kabupatenId);

            // Get kecamatan data for this kabupaten
            $kecamatanData = Province::getKecamatanDataWithStats($kabupatenId);

            // Get party data
            $parties = VoteData::getPartyData();

            // Get caleg data
            $calegData = VoteData::getCalegData();

            // Create caleg mapping by party
            $calegByPartai = [];
            foreach($calegData as $caleg) {
                $calegByPartai[$caleg->partai_id][] = $caleg;
            }

            // Sort caleg by nomor urut within each party
            foreach($calegByPartai as $partaiId => $calegs) {
                usort($calegByPartai[$partaiId], function($a, $b) {
                    return $a->nomor_urut - $b->nomor_urut;
                });
            }

            // Process each kecamatan
            $kecamatanDataProcessed = [];
            foreach($kecamatanData as $kecamatan) {
                // Get kelurahan data for this kecamatan
                $kelurahanData = Province::getKelurahanDataWithStats($kecamatan->id);

                // Get vote data for each kelurahan in this kecamatan
                $kelurahanVoteData = [];
                $kecamatanPartaiTotal = [];
                $kecamatanCalegTotal = [];

                foreach($kelurahanData as $kelurahan) {
                    $voteData = VoteData::getVoteDataByKelurahan($kelurahan->id);
                    if (!empty($voteData)) {
                        $kelurahanVotes = [];
                        $data = $voteData[0]; // Get first record

                        // Get party votes for this kelurahan
                        foreach($parties as $party) {
                            $suaraPartai = VoteData::getSuaraPartaiSaja($data->chart, $party->nomor_urut);
                            $kelurahanVotes['partai'][$party->nomor_urut] = $suaraPartai;

                            // Add to kecamatan total
                            if (!isset($kecamatanPartaiTotal[$party->nomor_urut])) {
                                $kecamatanPartaiTotal[$party->nomor_urut] = 0;
                            }
                            $kecamatanPartaiTotal[$party->nomor_urut] += $suaraPartai;
                        }

                        // Get caleg votes for this kelurahan
                        $calegVotes = VoteData::getSuaraCaleg($data->tbl);
                        foreach($calegVotes as $calegId => $suara) {
                            $kelurahanVotes['caleg'][$calegId] = $suara;

                            // Add to kecamatan total
                            if (!isset($kecamatanCalegTotal[$calegId])) {
                                $kecamatanCalegTotal[$calegId] = 0;
                            }
                            $kecamatanCalegTotal[$calegId] += $suara;
                        }

                        $kelurahanVoteData[$kelurahan->id] = [
                            'nama' => $kelurahan->nama_kelurahan,
                            'votes' => $kelurahanVotes
                        ];
                    }
                }

                $kecamatanDataProcessed[] = [
                    'id' => $kecamatan->id,
                    'nama' => $kecamatan->nama_kecamatan,
                    'kelurahan' => $kelurahanVoteData,
                    'partai_total' => $kecamatanPartaiTotal,
                    'caleg_total' => $kecamatanCalegTotal
                ];
            }

            return Inertia::render('Suara/CalegKabupatenIndex', [
                'kecamatanData' => $kecamatanDataProcessed,
                'parties' => $parties,
                'calegByPartai' => $calegByPartai,
                'kabupatenName' => $kabupatenInfo['kabupaten_name'],
                'provinceName' => $kabupatenInfo['province_name'],
                'provinceId' => $kabupatenInfo['province_id'],
                'kabupatenId' => $kabupatenId,
                'title' => "Data Suara Caleg per Kecamatan - {$kabupatenInfo['kabupaten_name']}"
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Suara/CalegKabupatenIndex', [
                'kecamatanData' => [],
                'parties' => [],
                'calegByPartai' => [],
                'kabupatenName' => 'Error',
                'provinceName' => 'Error',
                'provinceId' => 0,
                'kabupatenId' => $kabupatenId,
                'title' => 'Data Suara Caleg per Kecamatan - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function suaraKecamatan($kecamatanId)
    {
        try {
            // Get kecamatan info
            $kecamatanInfo = Province::getKecamatanInfo($kecamatanId);

            // Get kelurahan data for this kecamatan
            $kelurahanData = Province::getKelurahanDataWithStats($kecamatanId);

            // Get vote data for each kelurahan in this kecamatan
            $kelurahanVoteData = [];
            foreach($kelurahanData as $kelurahan) {
                $voteData = VoteData::getVoteDataByKelurahan($kelurahan->id);
                $tpsData = VoteData::getTpsDataByKelurahan($kelurahan->id);
                if (!empty($voteData)) {
                    $kelurahanVoteData[] = [
                        'kelurahan_info' => $kelurahan,
                        'vote_data' => $voteData[0], // Get first record
                        'tps_data' => $tpsData
                    ];
                }
            }

            // Get party data
            $parties = VoteData::getPartyData();

            // Get caleg data and process with party rows
            $calegData = VoteData::getCalegData();

            // Calculate caleg votes for this kecamatan
            $calegVotes = [];
            foreach($kelurahanVoteData as $kelData) {
                $tbl = json_decode($kelData['vote_data']->tbl, true) ?? [];
                foreach($tbl as $tpsId => $tpsData) {
                    foreach($tpsData as $calegId => $votes) {
                        if ($calegId !== 'null' && is_numeric($calegId) && $votes > 0) {
                            if (!isset($calegVotes[$calegId])) {
                                $calegVotes[$calegId] = 0;
                            }
                            $calegVotes[$calegId] += intval($votes);
                        }
                    }
                }
            }

            // Group caleg with votes by party and add party rows
            $calegWithVotes = [];
            $calegByPartai = [];

            // Group caleg by party
            foreach($calegData as $caleg) {
                if (isset($calegVotes[$caleg->id])) {
                    $caleg->total_suara = $calegVotes[$caleg->id];
                    $calegByPartai[$caleg->partai_id][] = $caleg;
                }
            }

            // Sort caleg within each party
            foreach($calegByPartai as $partaiId => $calegs) {
                usort($calegByPartai[$partaiId], function($a, $b) {
                    return $a->nomor_urut - $b->nomor_urut;
                });
            }

            // Create final caleg list with party rows
            foreach($parties as $party) {
                if (isset($calegByPartai[$party->nomor_urut])) {
                    // Add party row (votes without caleg)
                    $partaiRow = (object)[
                        'id' => 'partai_' . $party->nomor_urut,
                        'is_party_row' => true,
                        'partai_id' => $party->nomor_urut,
                        'nama' => 'Suara Partai (tanpa caleg)',
                        'nomor_urut' => 'PARTAI',
                        'jenis_kelamin' => '-'
                    ];
                    $calegWithVotes[] = $partaiRow;

                    // Add caleg for this party
                    foreach($calegByPartai[$party->nomor_urut] as $caleg) {
                        $calegWithVotes[] = $caleg;
                    }
                }
            }

            return Inertia::render('Suara/KecamatanIndex', [
                'kelurahanVoteData' => $kelurahanVoteData,
                'parties' => $parties,
                'calegData' => $calegWithVotes,
                'kecamatanName' => $kecamatanInfo['kecamatan_name'],
                'kabupatenName' => $kecamatanInfo['kabupaten_name'],
                'provinceName' => $kecamatanInfo['province_name'],
                'provinceId' => $kecamatanInfo['province_id'],
                'kabupatenId' => $kecamatanInfo['kabupaten_id'],
                'kecamatanId' => $kecamatanId,
                'title' => "Data Suara per Kelurahan - Kecamatan {$kecamatanInfo['kecamatan_name']}"
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Suara/KecamatanIndex', [
                'kelurahanVoteData' => [],
                'parties' => [],
                'calegData' => [],
                'kecamatanName' => 'Error',
                'kabupatenName' => 'Error',
                'provinceName' => 'Error',
                'provinceId' => 0,
                'kabupatenId' => 0,
                'kecamatanId' => $kecamatanId,
                'title' => 'Data Suara per Kelurahan - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function exportKecamatanSuaraExcel($kecamatanId)
    {
        try {
            // Get kecamatan info
            $kecamatanInfo = Province::getKecamatanInfo($kecamatanId);

            $fileName = 'Data_Suara_Kecamatan_' . str_replace(' ', '_', $kecamatanInfo['kecamatan_name']) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new KecamatanWebFormatExport(
                    $kecamatanId,
                    $kecamatanInfo['kecamatan_name'],
                    $kecamatanInfo['kabupaten_name'],
                    $kecamatanInfo['province_name']
                ),
                $fileName
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function tpsKecamatan($kecamatanId)
    {
        try {
            // Get kecamatan info
            $kecamatanInfo = Province::getKecamatanInfo($kecamatanId);

            // Get optimized TPS data for this kecamatan with vote data
            $tpsData = VoteData::getTpsDataWithVotesKecamatan($kecamatanId);

            // Get limited party data (only essential fields)
            $parties = VoteData::getEssentialPartyData();

            // Get caleg data for vote calculation
            $calegData = VoteData::getCalegData();

            // Get statistics summary instead of all caleg data
            $tpsStats = VoteData::getTpsStatsByKecamatan($kecamatanId);

            return Inertia::render('Suara/TpsKecamatanIndex', [
                'tpsData' => $tpsData,
                'parties' => $parties,
                'calegData' => $calegData,
                'tpsStats' => $tpsStats,
                'kecamatanName' => $kecamatanInfo['kecamatan_name'],
                'kabupatenName' => $kecamatanInfo['kabupaten_name'],
                'provinceName' => $kecamatanInfo['province_name'],
                'provinceId' => $kecamatanInfo['province_id'],
                'kabupatenId' => $kecamatanInfo['kabupaten_id'],
                'kecamatanId' => $kecamatanId,
                'title' => "Data TPS - Kecamatan {$kecamatanInfo['kecamatan_name']}"
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Suara/TpsKecamatanIndex', [
                'tpsData' => [],
                'parties' => [],
                'calegData' => [],
                'tpsStats' => [],
                'kecamatanName' => 'Error',
                'kabupatenName' => 'Error',
                'provinceName' => 'Error',
                'provinceId' => 0,
                'kabupatenId' => 0,
                'kecamatanId' => $kecamatanId,
                'title' => 'Data TPS - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function exportTpsExcel($kecamatanId)
    {
        try {
            // Get kecamatan info
            $kecamatanInfo = Province::getKecamatanInfo($kecamatanId);

            $fileName = 'Data_TPS_Kecamatan_' . str_replace(' ', '_', $kecamatanInfo['kecamatan_name']) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new TpsMultiSheetExport(
                    $kecamatanId,
                    $kecamatanInfo['kecamatan_name'],
                    $kecamatanInfo['kabupaten_name'],
                    $kecamatanInfo['province_name']
                ),
                $fileName
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function suaraCaleg($kelurahanId)
    {
        try {
            // Get kelurahan info
            $kelurahanInfo = VoteData::getKelurahanInfo($kelurahanId);

            // Get vote data for the kelurahan
            $voteData = VoteData::getVoteDataByKelurahan($kelurahanId);

            // Get party data
            $parties = VoteData::getPartyData();

            // Get caleg data
            $calegData = VoteData::getCalegData();

            // Process caleg votes data
            $calegVotes = [];
            if (!empty($voteData)) {
                foreach($voteData as $data) {
                    $votes = VoteData::getSuaraCaleg($data->tbl);
                    foreach($votes as $calegId => $totalSuara) {
                        if ($totalSuara > 0) {
                            $calegVotes[$calegId] = $totalSuara;
                        }
                    }
                }
            }

            // Create caleg mapping
            $calegMap = [];
            foreach($calegData as $caleg) {
                $calegMap[$caleg->id] = $caleg;
            }

            // Filter and organize caleg with votes
            $calegWithVotes = [];
            foreach($calegVotes as $calegId => $totalSuara) {
                if (isset($calegMap[$calegId])) {
                    $caleg = $calegMap[$calegId];
                    $calegWithVotes[] = (object)[
                        'id' => $caleg->id,
                        'nama' => $caleg->nama,
                        'nomor_urut' => $caleg->nomor_urut,
                        'jenis_kelamin' => $caleg->jenis_kelamin,
                        'partai_id' => $caleg->partai_id,
                        'total_suara' => $totalSuara
                    ];
                }
            }

            // Sort by party and nomor urut
            usort($calegWithVotes, function($a, $b) {
                if ($a->partai_id == $b->partai_id) {
                    return $a->nomor_urut - $b->nomor_urut;
                }
                return $a->partai_id - $b->partai_id;
            });

            return Inertia::render('Suara/CalegIndex', [
                'voteData' => $voteData,
                'parties' => $parties,
                'calegWithVotes' => $calegWithVotes,
                'calegMap' => $calegMap,
                'kelurahanName' => $kelurahanInfo['kelurahan_name'] ?? 'Unknown',
                'kecamatanName' => $kelurahanInfo['kecamatan_name'] ?? 'Unknown',
                'kabupatenName' => $kelurahanInfo['kabupaten_name'] ?? 'Unknown',
                'provinceName' => $kelurahanInfo['province_name'] ?? 'Unknown',
                'provinceId' => $kelurahanInfo['province_id'] ?? 0,
                'kabupatenId' => $kelurahanInfo['kabupaten_id'] ?? 0,
                'kecamatanId' => $kelurahanInfo['kecamatan_id'] ?? 0,
                'kelurahanId' => $kelurahanId,
                'title' => "Data Suara Caleg - {$kelurahanInfo['kelurahan_name']}"
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Suara/CalegIndex', [
                'voteData' => [],
                'parties' => [],
                'calegWithVotes' => [],
                'calegMap' => [],
                'kelurahanName' => 'Error',
                'kecamatanName' => 'Error',
                'kabupatenName' => 'Error',
                'provinceName' => 'Error',
                'provinceId' => 0,
                'kabupatenId' => 0,
                'kecamatanId' => 0,
                'kelurahanId' => $kelurahanId,
                'title' => 'Data Suara Caleg - Error',
                'error' => 'Database connection error: ' . $e->getMessage()
            ]);
        }
    }

    public function exportKecamatanCalegByDapil($kecamatanId)
    {
        try {
            // Get kecamatan info
            $kecamatanInfo = Province::getKecamatanInfo($kecamatanId);

            $fileName = 'Data_Caleg_Per_Dapil_Kecamatan_' . str_replace(' ', '_', $kecamatanInfo['kecamatan_name']) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new KecamatanCalegByDapilExport(
                    $kecamatanId,
                    $kecamatanInfo['kecamatan_name'],
                    $kecamatanInfo['kabupaten_name'],
                    $kecamatanInfo['province_name']
                ),
                $fileName
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export caleg by dapil failed: ' . $e->getMessage());
        }
    }
}
