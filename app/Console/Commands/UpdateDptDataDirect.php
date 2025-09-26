<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateDptDataDirect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-dpt-data-direct';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update pdpr_wil_tps with DPT data using direct SQL without transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting direct update of pdpr_wil_tps with DPT data...');

        // Set MySQL timeout settings for this session
        DB::statement('SET SESSION innodb_lock_wait_timeout = 300');
        DB::statement('SET SESSION lock_wait_timeout = 300');

        // Disable autocommit to improve performance
        DB::statement('SET autocommit = 0');

        try {
            // Check counts
            $tpsCount = DB::table('tps')->count();
            $pdprTpsCount = DB::table('pdpr_wil_tps')->count();

            $this->info("TPS table has {$tpsCount} records");
            $this->info("PDPR_WIL_TPS table has {$pdprTpsCount} records");

            // Perform the update with a simplified query
            $this->info('Executing update query...');

            $updateQuery = "
                UPDATE pdpr_wil_tps p, tps t
                SET
                    p.dpt_l = t.dpt_l,
                    p.dpt_p = t.dpt_p,
                    p.total_dpt = t.total_dpt
                WHERE p.kel_kode = t.kode_kel
                  AND p.tps_kode = CONCAT(t.kode_kel, LPAD(t.no_tps, 3, '0'))
            ";

            $result = DB::unprepared($updateQuery);

            // Commit the changes
            DB::statement('COMMIT');

            $this->info('Update completed!');

            // Check how many records were updated
            $updatedCount = DB::select("
                SELECT COUNT(*) as count
                FROM pdpr_wil_tps p
                INNER JOIN tps t ON p.kel_kode = t.kode_kel AND p.tps_kode = CONCAT(t.kode_kel, LPAD(t.no_tps, 3, '0'))
                WHERE p.total_dpt = t.total_dpt AND p.total_dpt > 0
            ");

            $this->info("Records with matching DPT data: {$updatedCount[0]->count}");

            // Show sample updated records
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

        } catch (\Exception $e) {
            DB::statement('ROLLBACK');
            $this->error('Update failed: ' . $e->getMessage());
            return 1;
        } finally {
            // Re-enable autocommit
            DB::statement('SET autocommit = 1');
        }

        return 0;
    }
}
