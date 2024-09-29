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
        Schema::dropIfExists('credit_repayment_logs');
        Schema::create('credit_repayment_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
            $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');
            $table->foreignId('loan_id')->constrained('credit_loans')->onDelete('cascade');
            $table->string('reference', 50)->unique();
            $table->foreignId('payment_method_id')->constrained('credit_payment_methods')->onDelete('cascade')->nullable();

            $table->decimal('total_amount_paid', 18, 2)->nullable();
            $table->decimal('capital_amount', 18, 2)->nullable();
            $table->decimal('interest_amount', 18, 2)->nullable();
            $table->decimal('penalty_fee', 18, 2)->nullable();

            $table->string('txn_status')->nullable();
            $table->string('channel')->nullable(); // e.g paystack, etc.
            $table->json('channel_response')->nullable();
            $table->string('channel_reference')->nullable();
            $table->decimal('channel_fee')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_repayment_logs');
    }
};
