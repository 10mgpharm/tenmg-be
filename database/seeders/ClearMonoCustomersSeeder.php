<?php

namespace Database\Seeders;

use App\Models\MonoCustomer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearMonoCustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Clears all records from the mono_customers table.
     */
    public function run(): void
    {
        $this->command->info('Clearing mono_customers table...');

        // Disable foreign key checks to avoid constraint errors
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            $count = MonoCustomer::count();

            // Truncate the table (faster than delete for large datasets)
            MonoCustomer::truncate();

            $this->command->info("Successfully cleared {$count} record(s) from mono_customers table.");

            Log::info('Mono customers table cleared', [
                'records_deleted' => $count,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            $this->command->error('Failed to clear mono_customers table: '.$e->getMessage());

            Log::error('Failed to clear mono_customers table', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
