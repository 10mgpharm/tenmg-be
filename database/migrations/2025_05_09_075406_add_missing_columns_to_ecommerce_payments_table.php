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
        Schema::table('ecommerce_payments', function (Blueprint $table) {

            if (!Schema::hasColumn('ecommerce_payments', 'wallet_id')) {
                $table->unsignedBigInteger('wallet_id')->nullable()->after('meta');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'wallet_type')) {
                $table->string('wallet_type')->nullable()->after('wallet_id');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'business_id')) {
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete()->after('wallet_type');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'ecommerce_transaction_id')) {
                $table->foreignId('ecommerce_transaction_id')->nullable()->constrained()->nullOnDelete()->after('business_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_payments', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['ecommerce_transaction_id']);

            $table->dropColumn([
                'wallet_id',
                'wallet_type',
                'business_id',
                'ecommerce_transaction_id',
            ]);
        });
    }
};
