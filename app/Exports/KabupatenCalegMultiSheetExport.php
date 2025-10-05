<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KabupatenCalegMultiSheetExport implements WithMultipleSheets
{
    protected $kabupatenId;
    protected $kabupatenName;
    protected $provinceName;

    public function __construct($kabupatenId, $kabupatenName, $provinceName)
    {
        $this->kabupatenId = $kabupatenId;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;
    }

    public function sheets(): array
    {
        try {
            // Get all kecamatan in this kabupaten
            $kecamatanData = \App\Models\Province::getKecamatanDataWithStats($this->kabupatenId);

            $sheets = [];
            $sheetCount = 0;
            $maxSheets = 10; // Limit sheets to prevent timeout

            if (empty($kecamatanData) || $kecamatanData->isEmpty()) {
                // Return a single sheet with "No Data" message if no kecamatan found
                $sheets[] = new \App\Exports\KabupatenNoDataSheet($this->kabupatenName);
                return $sheets;
            }

            foreach($kecamatanData as $kecamatan) {
                if ($sheetCount >= $maxSheets) {
                    break;
                }

                try {
                    $sheets[] = new KabupatenKecamatanCalegSheet(
                        $kecamatan->id,
                        $kecamatan->nama_kecamatan,
                        $this->kabupatenName,
                        $this->provinceName
                    );
                    $sheetCount++;
                } catch (\Exception $e) {
                    // Skip this kecamatan if there's an error, continue with others
                    continue;
                }
            }

            // If no sheets were created, add a "No Data" sheet
            if (empty($sheets)) {
                $sheets[] = new \App\Exports\KabupatenNoDataSheet($this->kabupatenName);
            }

            return $sheets;
        } catch (\Exception $e) {
            // Return error sheet if complete failure
            return [new \App\Exports\KabupatenNoDataSheet($this->kabupatenName, 'Error: ' . $e->getMessage())];
        }
    }
}