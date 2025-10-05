<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KabupatenTestExport implements FromArray, WithHeadings
{
    protected $kabupatenId;
    protected $kabupatenName;

    public function __construct($kabupatenId, $kabupatenName)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
    }

    public function array(): array
    {
        // Get basic kabupaten info
        try {
            $kecamatanData = \App\Models\Province::getKecamatanDataWithStats($this->kabupatenId);
            $data = [];
            $counter = 1;

            foreach($kecamatanData as $kecamatan) {
                if ($counter > 20) break; // Limit to prevent timeout

                $data[] = [
                    $counter,
                    $kecamatan->nama_kecamatan ?? 'N/A',
                    number_format($kecamatan->jumlah_kelurahan ?? 0, 0, ',', '.'),
                    number_format($kecamatan->jumlah_tps ?? 0, 0, ',', '.'),
                    number_format($kecamatan->total_dpt ?? 0, 0, ',', '.'),
                    'Ringkasan Data Kecamatan'
                ];
                $counter++;
            }

            return $data;
        } catch (\Exception $e) {
            return [
                ['1', 'Error loading data: ' . $e->getMessage(), '0', '0', '0', 'Error'],
            ];
        }
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Kecamatan',
            'Jumlah Kelurahan',
            'Jumlah TPS',
            'Total DPT',
            'Status'
        ];
    }
}