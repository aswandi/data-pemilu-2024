<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdatePdprTpsWithDptData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-pdpr-tps-with-dpt-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update pdpr_wil_tps table with DPT data from tps table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting update of pdpr_wil_tps with DPT data...');

        // Check if both tables exist and have data
        $tpsCount = DB::table('tps')->count();
        $pdprTpsCount = DB::table('pdpr_wil_tps')->count();

        $this->info("TPS table has {$tpsCount} records");
        $this->info("PDPR_WIL_TPS table has {$pdprTpsCount} records");

        if ($tpsCount == 0) {
            $this->error('TPS table is empty. Please import TPS data first.');
            return 1;
        }

        if ($pdprTpsCount == 0) {
            $this->error('PDPR_WIL_TPS table is empty.');
            return 1;
        }

        // Check how many records can be matched
        // The tps_kode in pdpr_wil_tps is constructed as kel_kode + zero-padded no_tps
        $matchableRecords = DB::select("
            SELECT COUNT(*) as count
            FROM pdpr_wil_tps p
            INNER JOIN tps t ON p.kel_kode = t.kode_kel AND p.tps_kode = CONCAT(t.kode_kel, LPAD(t.no_tps, 3, '0'))
        ");

        $this->info("Found {$matchableRecords[0]->count} records that can be matched");

        if ($matchableRecords[0]->count == 0) {
            $this->error('No matching records found between pdpr_wil_tps and tps tables');
            return 1;
        }

        // Perform the update using batch approach to avoid lock timeout
        $this->info('Updating records in batches...');

        $totalUpdated = 0;
        $batchSize = 10000;
        $offset = 0;

        while (true) {
            // Get a batch of TPS records
            $tpsBatch = DB::table('tps')
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            if ($tpsBatch->isEmpty()) {
                break;
            }

            // Update records for this batch
            $batchUpdated = 0;
            foreach ($tpsBatch as $tpsRecord) {
                $tpsCode = $tpsRecord->kode_kel . str_pad($tpsRecord->no_tps, 3, '0', STR_PAD_LEFT);

                $updated = DB::table('pdpr_wil_tps')
                    ->where('kel_kode', $tpsRecord->kode_kel)
                    ->where('tps_kode', $tpsCode)
                    ->update([
                        'dpt_l' => $tpsRecord->dpt_l,
                        'dpt_p' => $tpsRecord->dpt_p,
                        'total_dpt' => $tpsRecord->total_dpt,
                        'updated_at' => now()
                    ]);

                $batchUpdated += $updated;
            }

            $totalUpdated += $batchUpdated;
            $offset += $batchSize;

            $this->info("Processed batch at offset {$offset}, updated {$batchUpdated} records. Total: {$totalUpdated}");

            // Small delay to reduce database load
            usleep(100000); // 0.1 second
        }

        $this->info("Update completed successfully!");
        $this->info("Total records updated: {$totalUpdated}");

        // Show some sample updated records
        $this->info('Sample of updated records:');
        $samples = DB::select("
            SELECT p.nama, p.kel_kode, p.tps_kode, p.dpt_l, p.dpt_p, p.total_dpt
            FROM pdpr_wil_tps p
            WHERE p.total_dpt > 0
            ORDER BY p.id
            LIMIT 5
        ");

        $this->table(['Nama TPS', 'Kel Kode', 'TPS Kode', 'DPT L', 'DPT P', 'Total DPT'],
            array_map(function($row) {
                return [
                    $row->nama,
                    $row->kel_kode,
                    $row->tps_kode,
                    $row->dpt_l,
                    $row->dpt_p,
                    $row->total_dpt
                ];
            }, $samples)
        );

        return 0;
    }
}
