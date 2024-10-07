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
        Schema::create('ecommerce_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->nullOnDelete()->index();
            $table->decimal('previous_balance', 18, 2);
            $table->decimal('current_balance', 18, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_wallets');
    }
};
