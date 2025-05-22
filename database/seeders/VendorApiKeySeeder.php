<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Models\Business;
use App\Repositories\ApiKeyRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all vendor businesses
        $vendors = Business::where('type', BusinessType::VENDOR)->get();

        foreach ($vendors as $vendor) {
            DB::transaction(function () use ($vendor) {
                (new ApiKeyRepository)->createVendorApiKey($vendor);
            });
        }

        $this->command->info('Vendor API Keys have been regenerated successfully.');
    }
}
