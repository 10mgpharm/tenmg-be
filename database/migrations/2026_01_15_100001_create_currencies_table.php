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
        Schema::create('currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('classification', ['fiat', 'crypto']);
            $table->string('name', 125);
            $table->string('code', 10)->nullable()->unique();
            $table->string('symbol', 10)->nullable();
            $table->string('slug', 10)->nullable()->unique();
            $table->tinyInteger('decimal_places')->nullable();
            $table->string('icon', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('tier_1_limits')->nullable();
            $table->json('tier_2_limits')->nullable();
            $table->json('tier_3_limits')->nullable();
            $table->string('country_code', 3)->nullable();
            $table->uuid('virtual_account_provider')->nullable();
            $table->uuid('temp_virtual_account_provider')->nullable();
            $table->uuid('virtual_card_provider')->nullable();
            $table->uuid('bank_transfer_collection_provider')->nullable();
            $table->uuid('mobile_money_collection_provider')->nullable();
            $table->uuid('bank_transfer_payout_provider')->nullable();
            $table->uuid('mobile_money_payout_provider')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('virtual_account_provider')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->foreign('temp_virtual_account_provider')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->foreign('virtual_card_provider')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->foreign('bank_transfer_collection_provider')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->foreign('mobile_money_collection_provider')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->foreign('bank_transfer_payout_provider')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->foreign('mobile_money_payout_provider')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
