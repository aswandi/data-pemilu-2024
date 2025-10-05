<?php

namespace App\Exports;

use App\Models\Province;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VoteDataExport implements WithMultipleSheets
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
        $sheets = [];

        // Main summary sheet
        $sheets[] = new VoteDataSummarySheet($this->kabupatenId, $this->kabupatenName, $this->provinceName);

        // Combined sheet with all kecamatan TPS data
        $sheets[] = new CombinedKecamatanTpsSheet($this->kabupatenId, $this->kabupatenName, $this->provinceName);

        return $sheets;
    }

}