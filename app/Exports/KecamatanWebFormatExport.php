<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KecamatanWebFormatExport implements WithMultipleSheets
{
    protected $kecamatanId;
    protected $kecamatanName;
    protected $kabupatenName;
    protected $provinceName;

    public function __construct($kecamatanId, $kecamatanName, $kabupatenName, $provinceName)
    {
        $this->kecamatanId = $kecamatanId;
        $this->kecamatanName = $kecamatanName;
        $this->kabupatenName = $kabupatenName;
        $this->provinceName = $provinceName;
    }

    public function sheets(): array
    {
        return [
            new KecamatanPartaiWebSheet($this->kecamatanId, $this->kecamatanName, $this->kabupatenName, $this->provinceName),
            new KecamatanCalegWebSheet($this->kecamatanId, $this->kecamatanName, $this->kabupatenName, $this->provinceName),
        ];
    }
}