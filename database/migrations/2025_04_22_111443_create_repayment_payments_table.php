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
        Schema::create('credit_repayment_payments', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['initiated','success','pending','failed','abandoned'])->default('initiated');
            $table->string('reference')->nullable();
            $table->string('external_reference')->nullable();
            $table->integer('loan_id')->constrained('credit_loans')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('channel')->nullable();
            $table->string('currency')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('comment')->nullable();
            $table->integer('business_id')->constrained('businesses')->nullOnDelete();
            $table->integer('customer_id')->constrained('credit_customers')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayment_payments');
    }
};
