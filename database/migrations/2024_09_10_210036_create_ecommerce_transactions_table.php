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
        Schema::create('ecommerce_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecommerce_wallet_id')->nullable()->constrained('ecommerce_wallets')->nullOnDelete();

            $table->foreignId('supplier_id')->nullable()->constrained('businesses')->nullOnDelete();

            $table->foreignId('ecommerce_order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->enum('txn_type', ['CREDIT', 'DEBIT']);
            $table->string('txn_group')->comment("['ORDER_PAYMENT', 'REFUND', 'WITHDRAWAL', 'PAYOUT']");
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->enum('status', ['HOLD', 'CREDIT', 'DEBIT'])
                ->comment('CREDIT (pending payout), and DEBIT (payout/withdrawal) of supplier’s funds');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_transactions');
    }
};
