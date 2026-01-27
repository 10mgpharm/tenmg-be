<?php

namespace Database\Seeders;

use App\Models\Wallet;
use Illuminate\Database\Seeder;

class FundWalletsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds 200,000 to every wallet for testing purposes
     */
    public function run(): void
    {
        $this->command->info('💰 Adding ₦200,000 to all wallets...');

        $wallets = Wallet::all();

        if ($wallets->isEmpty()) {
            $this->command->warn('⚠️  No wallets found in the database.');

            return;
        }

        $fundAmount = 200000;
        $updatedCount = 0;

        foreach ($wallets as $wallet) {
            $oldBalance = $wallet->balance;
            $wallet->balance = $wallet->balance + $fundAmount;
            $wallet->save();
            $updatedCount++;

            $this->command->info("  ✓ Wallet {$wallet->id}: ₦{$oldBalance} → ₦{$wallet->balance}");
        }

        $this->command->info('');
        $this->command->info("✅ Successfully funded {$updatedCount} wallets with ₦200,000 each!");
    }
}
