<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTpsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-tps-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import TPS data from CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $csvFile = base_path('backup/DPT 2024_Frame1.csv');

        if (!file_exists($csvFile)) {
            $this->error('CSV file not found: ' . $csvFile);
            return 1;
        }

        $this->info('Starting TPS data import...');

        // Truncate existing data
        DB::table('tps')->truncate();

        $handle = fopen($csvFile, 'r');
        $header = true;
        $imported = 0;

        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            // Skip header row
            if ($header) {
                $header = false;
                continue;
            }

            // Parse the data - first column contains combined data, ignore it and use individual columns
            if (count($data) >= 10) {
                DB::table('tps')->insert([
                    'pro' => $data[1], // pro column
                    'kab' => $data[2], // kab column
                    'kec' => $data[3], // kec column
                    'kel' => $data[4], // kel column
                    'kode_kel' => $data[5], // kode_kel column
                    'no_tps' => (int) $data[6], // no_tps column
                    'dpt_l' => (int) $data[7], // DPT_L column
                    'dpt_p' => (int) $data[8], // DPT_P column
                    'total_dpt' => (int) $data[9], // Total_DPT column
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $imported++;

                if ($imported % 1000 == 0) {
                    $this->info("Imported $imported records...");
                }
            }
        }

        fclose($handle);

        $this->info("Import completed! Total records imported: $imported");
        return 0;
    }
}
