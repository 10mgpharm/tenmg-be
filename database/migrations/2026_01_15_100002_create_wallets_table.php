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
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->enum('wallet_type', [
                'vendor_payout',
                'vendor_credit_voucher',
                'lender_investment',
                'lender_deposit',
                'lender_ledger',
                'admin_main',
            ]);
            $table->uuid('currency_id');
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('wallet_name')->nullable();
            $table->timestamps();

            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('restrict');

            $table->unique(['business_id', 'wallet_type', 'currency_id']);
            $table->index('wallet_type');
            $table->index('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
