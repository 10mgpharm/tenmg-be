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
        Schema::create('service_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->json('config')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_bvn_verification_provider')->default(false);
            $table->boolean('is_virtual_account_provider')->default(false);
            $table->boolean('is_virtual_card_provider')->default(false);
            $table->boolean('is_physical_card_provider')->default(false);
            $table->boolean('is_checkout_provider')->default(false);
            $table->boolean('is_bank_payout_provider')->default(false);
            $table->boolean('is_mobile_money_payout_provider')->default(false);
            $table->boolean('is_identity_verification_provider')->default(false);
            $table->json('currencies_supported')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
