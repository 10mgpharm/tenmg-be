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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->uuid('wallet_id');
            $table->uuid('currency_id');
            $table->enum('transaction_category', ['debit', 'credit']);
            $table->string('transaction_type');
            $table->string('transaction_method')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('transaction_narration')->nullable();
            $table->text('transaction_description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->uuid('processor')->nullable();
            $table->string('processor_reference')->nullable();
            $table->uuid('beneficiary_id')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->json('transaction_data')->nullable();
            $table->timestamps();

            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallets')
                ->onDelete('restrict');

            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('restrict');

            $table->foreign('processor')
                ->references('id')
                ->on('service_providers')
                ->onDelete('set null');

            $table->index('transaction_category');
            $table->index('transaction_type');
            $table->index('status');
            $table->index('transaction_reference');
            $table->index(['business_id', 'status']);
            $table->index(['wallet_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
