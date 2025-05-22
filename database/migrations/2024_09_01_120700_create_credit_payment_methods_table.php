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
        if (! Schema::hasTable('credit_payment_methods')) {
            Schema::create('credit_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique()->comment('10MG_VOUCHER, BANK_TRANSFER, DEBIT_CARD, ACCOUNT_MANDATE');
                $table->boolean('active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_payment_methods');
    }
};
