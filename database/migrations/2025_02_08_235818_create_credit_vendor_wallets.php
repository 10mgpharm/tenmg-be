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
        Schema::create('credit_vendor_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('businesses')->onDelete('cascade');

            // payout wallet represent a wallet where the vendor get paid installmentally for each due date on loan their customer took
            // credit_voucher wallet represent total amount that has been given out as credit to a vendor customer
            $table->enum('type', ['payout', 'credit_voucher']);

            $table->decimal('current_balance', 18, 2)->default(0);
            $table->decimal('prev_balance', 18, 2)->default(0);
            $table->string('last_transaction_ref')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_vendor_wallets');
    }
};
