<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('wallets')) {
            DB::statement("ALTER TABLE wallets MODIFY COLUMN wallet_type ENUM('vendor_payout_wallet', 'lender_wallet', 'admin_wallet')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wallets')) {
            DB::statement("ALTER TABLE wallets MODIFY COLUMN wallet_type ENUM('vendor_payout', 'vendor_credit_voucher', 'lender_investment', 'lender_deposit', 'lender_ledger', 'admin_main')");
        }
    }
};
