<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecommerce_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_transactions', 'ecommerce_wallet_id')) {
                // Drop foreign key before dropping the column
                $table->dropForeign(['ecommerce_wallet_id']);
                $table->dropColumn('ecommerce_wallet_id');
            }

            if (!Schema::hasColumn('ecommerce_transactions', 'walletable_id') &&
                !Schema::hasColumn('ecommerce_transactions', 'walletable_type')) {
                    $table->unsignedBigInteger('walletable_id')->nullable()->after('supplier_id');
                    $table->string('walletable_type')->nullable()->after('walletable_id');
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_transactions', function (Blueprint $table) {
            // Drop polymorphic columns if they exist
            if (Schema::hasColumn('ecommerce_transactions', 'walletable_id') &&
                Schema::hasColumn('ecommerce_transactions', 'walletable_type')) {
                $table->dropMorphs('walletable');
            }
    
            // Restore ecommerce_wallet_id
            if (!Schema::hasColumn('ecommerce_transactions', 'ecommerce_wallet_id')) {
                $table->foreignId('ecommerce_wallet_id')
                    ->nullable()
                    ->constrained()
                    ->after('supplier_id');
            }
        });
    }
};
