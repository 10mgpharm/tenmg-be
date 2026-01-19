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
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->uuid('currency_id')->nullable();
            $table->uuid('wallet_id')->nullable()->unique();
            $table->enum('type', ['individual', 'corporate']);
            $table->uuid('provider');
            $table->string('provider_reference')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('account_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable()->unique();
            $table->string('account_type')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('country_code', 3)->nullable();
            $table->string('iban')->nullable();
            $table->string('check_number')->nullable();
            $table->string('sort_code')->nullable();
            $table->string('bank_swift_code')->nullable();
            $table->string('addressable_in', 10)->nullable();
            $table->text('bank_address')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('set null');

            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallets')
                ->onDelete('set null');

            $table->foreign('provider')
                ->references('id')
                ->on('service_providers')
                ->onDelete('restrict');

            $table->index('status');
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_accounts');
    }
};
