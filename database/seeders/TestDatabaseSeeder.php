<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's test database.
     * This seeder is specifically for creating test data for vendors and lenders.
     *
     * Run with: php artisan db:seed --class=TestDatabaseSeeder
     */
    public function run(): void
    {
        $this->command->info('🧪 Running Test Data Seeders...');
        $this->command->info('');

        $this->call([
            // First ensure roles and currencies exist
            RolePermissionSeeder::class,
            CurrencySeeder::class,

            // Then create test data
            TestAdminUserSeeder::class,
            TestVendorLenderSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✅ Test data seeding completed!');
        $this->command->info('');
        $this->command->info('📄 See QUICK_TEST_CREDENTIALS.md for login details');
        $this->command->info('🔑 Universal password: pass wrld');
    }
}
