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
        Schema::create('credit_lenders_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_id')->constrained('businesses')->onDelete('cascade');
            $table->enum('type', ['investment', 'deposit']);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->decimal('prev_balance', 10, 2)->default(0);
            $table->string('last_transaction_ref')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_lenders_wallets');
    }
};
