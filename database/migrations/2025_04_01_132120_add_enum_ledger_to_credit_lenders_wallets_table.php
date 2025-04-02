<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('credit_lenders_wallets')) {
            DB::statement("ALTER TABLE credit_lenders_wallets MODIFY COLUMN type ENUM('investment', 'deposit', 'ledger')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('credit_lenders_wallets')) {
            DB::statement("ALTER TABLE credit_lenders_wallets MODIFY COLUMN type ENUM('investment', 'deposit')");
        }
    }
};
