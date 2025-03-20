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
        Schema::create('credit_transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('identifier');
            $table->decimal('amount', 10,2);
            $table->enum('type', ['DEBIT', 'CREDIT'])->nullable();
            $table->string('transaction_group');
            $table->string('description');
            $table->string('status');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('wallet_id')->nullable()->constrained('credit_lenders_wallets')->nullOnDelete();
            $table->foreignId('loan_application_id')->nullable()->constrained('credit_applications')->nullOnDelete();
            $table->json('meta')->nullable(); //stores response from gateway
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transaction_histories');
    }
};
